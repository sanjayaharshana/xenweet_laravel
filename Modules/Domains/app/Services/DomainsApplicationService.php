<?php

namespace Modules\Domains\Services;

use App\Models\Hosting;
use App\Services\HostingCliProvisioner;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Domains\Models\HostDomain;
use Modules\Domains\Models\HostDomainRedirect;
use Modules\Domains\Models\HostDomainZoneRecord;

class DomainsApplicationService
{
    public function __construct(
        private readonly HostingCliProvisioner $provisioner,
        private readonly LiveDnsLookupService $liveDnsLookup
    ) {
    }

    public function buildIndexData(Hosting $hosting, string $tab, mixed $filterZoneRaw, bool $refreshDns): array
    {
        $hostDomains = collect();
        $redirects = collect();
        $zoneRecords = collect();
        $hasZoneTable = Schema::hasTable('host_domain_zone_records');
        $filterZone = $this->normalizedFilterZone($hosting, $filterZoneRaw);

        if (class_exists(HostDomain::class) && Schema::hasTable('host_domains')) {
            $hostDomains = HostDomain::query()
                ->where('hosting_id', $hosting->id)
                ->orderByDesc('id')
                ->get();
        }
        if (class_exists(HostDomainRedirect::class) && Schema::hasTable('host_domain_redirects')) {
            $redirects = HostDomainRedirect::query()
                ->where('hosting_id', $hosting->id)
                ->orderByDesc('id')
                ->get();
        }
        if ($hasZoneTable && class_exists(HostDomainZoneRecord::class)) {
            $query = HostDomainZoneRecord::query()->where('hosting_id', $hosting->id);
            if ($filterZone !== null) {
                $query->where('zone_domain', $filterZone);
            }
            $zoneRecords = $query
                ->orderByDesc('id')
                ->get();
        }

        $zoneDomainOptions = $this->buildZoneDomainOptions($hosting, $hostDomains);

        $liveDnsRows = collect();
        $liveDnsError = null;
        $liveDnsFetchedAt = null;
        if ($tab === 'zone' && $zoneDomainOptions->isNotEmpty()) {
            $zonesToQuery = $filterZone !== null ? [$filterZone] : $zoneDomainOptions->all();
            $result = $this->liveDnsLookup->fetchForZones($zonesToQuery, $refreshDns);
            $liveDnsRows = $result['rows'];
            $liveDnsFetchedAt = $result['fetched_at'];
            if (! $result['ok']) {
                $liveDnsError = $result['error'] ?? 'Live DNS could not be loaded.';
            }
        }

        return [
            'hostDomains' => $hostDomains,
            'redirects' => $redirects,
            'zoneRecords' => $zoneRecords,
            'hasZoneTable' => $hasZoneTable,
            'filterZone' => $filterZone,
            'zoneDomainOptions' => $zoneDomainOptions,
            'liveDnsRows' => $liveDnsRows,
            'liveDnsError' => $liveDnsError,
            'liveDnsFetchedAt' => $liveDnsFetchedAt,
        ];
    }

    public function storeDomain(Hosting $hosting, array $validated): array
    {
        if (! Schema::hasTable('host_domains')) {
            return ['type' => 'error', 'message' => 'Database is not ready. Run migrations: php artisan migrate'];
        }
        $hasDocumentRootColumn = Schema::hasColumn('host_domains', 'document_root');

        $type = (string) $validated['domain_type'];
        $rootMode = (string) ($validated['root_mode'] ?? 'shared');
        $documentRoot = trim((string) ($validated['document_root'] ?? ''));
        if ($rootMode !== 'custom') {
            $documentRoot = '';
        }
        if ($rootMode === 'custom' && $documentRoot === '') {
            return ['type' => 'validation', 'errors' => ['document_root' => 'Please enter a custom root path.']];
        }
        if ($rootMode === 'custom' && ! str_starts_with($documentRoot, '/')) {
            return ['type' => 'validation', 'errors' => ['document_root' => 'Root path must start with "/" (absolute path).']];
        }
        $share = $rootMode !== 'custom';

        if ($type === 'registered') {
            $name = Hosting::normalizeDomainName((string) ($validated['domain_name'] ?? ''));
            if ($name === '') {
                return ['type' => 'validation', 'errors' => ['domain_name' => 'Please enter a valid domain.']];
            }
        } else {
            $name = $this->generateTemporaryDomain($hosting);
        }

        if (strcasecmp($name, $hosting->siteHost()) === 0) {
            return ['type' => 'validation', 'errors' => ['domain_name' => 'This domain is already the primary domain for this account.']];
        }

        if ($this->domainExistsInSystem($name)) {
            return ['type' => 'validation', 'errors' => ['domain_name' => 'This domain is already in use.']];
        }

        $payload = [
            'hosting_id' => $hosting->id,
            'type' => $type,
            'domain' => $name,
            'share_document_root' => $share,
        ];
        if ($hasDocumentRootColumn) {
            $payload['document_root'] = $share ? null : $documentRoot;
        }

        HostDomain::query()->create($payload);

        $vhost = $this->provisioner->reapplyWebVhost($hosting->refresh());
        if (! $vhost['success']) {
            return [
                'type' => 'warning',
                'success' => 'Domain saved: '.$name,
                'error' => 'Nginx was not updated so the new hostname may not be served yet: '.$vhost['message'],
            ];
        }

        $msg = 'Domain added: '.$name.'. '.trim((string) ($vhost['message'] ?? ''), '.');

        return ['type' => 'success', 'success' => $msg];
    }

    public function destroyDomain(Hosting $hosting, HostDomain $hostDomain): array
    {
        if (! Schema::hasTable('host_domains')) {
            return ['type' => 'error', 'message' => 'Database is not ready. Run migrations: php artisan migrate'];
        }
        if ((int) $hostDomain->hosting_id !== (int) $hosting->id) {
            return ['type' => 'error', 'message' => 'The selected domain does not belong to this hosting account.'];
        }

        $name = (string) $hostDomain->domain;
        $hostDomain->delete();

        $vhost = $this->provisioner->reapplyWebVhost($hosting->refresh());
        if (! $vhost['success']) {
            return [
                'type' => 'warning',
                'success' => 'Domain removed: '.$name,
                'error' => 'Nginx was not updated after delete: '.$vhost['message'],
            ];
        }

        $msg = 'Domain removed: '.$name.'. '.trim((string) ($vhost['message'] ?? ''), '.');

        return ['type' => 'success', 'success' => $msg];
    }

    public function storeRedirect(Hosting $hosting, array $validated): array
    {
        if (! Schema::hasTable('host_domain_redirects')) {
            return ['type' => 'error', 'message' => 'Redirects table is missing. Run migrations: php artisan migrate'];
        }

        $source = Hosting::normalizeDomainName((string) $validated['source_domain']);
        if ($source === '') {
            return ['type' => 'validation', 'errors' => ['source_domain' => 'Please select a valid source domain.']];
        }
        if (! $this->belongsToHosting($hosting, $source)) {
            return ['type' => 'validation', 'errors' => ['source_domain' => 'Selected domain does not belong to this hosting account.']];
        }

        HostDomainRedirect::query()->updateOrCreate(
            ['hosting_id' => $hosting->id, 'source_domain' => $source],
            [
                'redirect_type' => (string) $validated['redirect_type'],
                'redirect_url' => (string) $validated['redirect_url'],
            ]
        );

        return ['type' => 'success', 'success' => 'Redirect saved for '.$source.'.'];
    }

    public function destroyRedirect(Hosting $hosting, HostDomainRedirect $redirect): array
    {
        if ((int) $redirect->hosting_id !== (int) $hosting->id) {
            return ['type' => 'error', 'message' => 'The selected redirect does not belong to this hosting account.'];
        }

        $source = $redirect->source_domain;
        $redirect->delete();

        return ['type' => 'success', 'success' => 'Redirect removed for '.$source.'.'];
    }

    public function storeZoneRecord(Hosting $hosting, array $validated): array
    {
        if (! Schema::hasTable('host_domain_zone_records')) {
            return ['type' => 'error', 'message' => 'Zone records table is missing. Run migrations: php artisan migrate'];
        }

        $zoneDomain = Hosting::normalizeDomainName((string) $validated['zone_domain']);
        if ($zoneDomain === '' || ! $this->belongsToHosting($hosting, $zoneDomain)) {
            return ['type' => 'validation', 'errors' => ['zone_domain' => 'Select a valid zone (domain) for this hosting account.']];
        }

        $name = trim((string) $validated['record_name']);
        if ($name === '') {
            return ['type' => 'validation', 'errors' => ['record_name' => 'Name is required (use @ for the zone apex).']];
        }

        $type = (string) $validated['record_type'];
        $value = trim((string) $validated['record_value']);
        $mx = $type === 'MX' ? ($validated['mx_priority'] ?? null) : null;

        if ($type === 'A' && ! filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return ['type' => 'validation', 'errors' => ['record_value' => 'A record value must be a valid IPv4 address.']];
        }
        if ($type === 'AAAA' && ! filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return ['type' => 'validation', 'errors' => ['record_value' => 'AAAA record value must be a valid IPv6 address.']];
        }

        $ttl = (int) ($validated['ttl'] ?? 3600);
        if ($ttl < 60) {
            $ttl = 3600;
        }

        HostDomainZoneRecord::query()->create([
            'hosting_id' => $hosting->id,
            'zone_domain' => $zoneDomain,
            'record_name' => $name,
            'record_type' => $type,
            'record_value' => $value,
            'mx_priority' => $mx !== null && $mx !== '' ? (int) $mx : null,
            'ttl' => $ttl,
        ]);

        return [
            'type' => 'success',
            'success' => 'DNS record added. Apply this to your live DNS at your registrar or nameserver; the panel stores it for reference and future automation.',
        ];
    }

    public function destroyZoneRecord(Hosting $hosting, HostDomainZoneRecord $zoneRecord): array
    {
        if ((int) $zoneRecord->hosting_id !== (int) $hosting->id) {
            return ['type' => 'error', 'message' => 'The selected record does not belong to this hosting account.'];
        }

        $zoneRecord->delete();

        return ['type' => 'success', 'success' => 'DNS record removed.'];
    }

    public function normalizedFilterZone(Hosting $hosting, mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $zone = Hosting::normalizeDomainName((string) $raw);
        if ($zone === '' || ! $this->belongsToHosting($hosting, $zone)) {
            return null;
        }

        return $zone;
    }

    private function buildZoneDomainOptions(Hosting $hosting, Collection $hostDomains): Collection
    {
        if ($hostDomains->isEmpty()) {
            return collect([$hosting->siteHost()]);
        }

        return collect([$hosting->siteHost()])
            ->merge($hostDomains->pluck('domain'))
            ->filter(fn ($d) => trim((string) $d) !== '')
            ->unique()
            ->values();
    }

    private function generateTemporaryDomain(Hosting $hosting): string
    {
        $base = $hosting->siteHost();
        for ($i = 0; $i < 40; $i++) {
            $token = Str::lower(Str::random(8));
            $candidate = 't-'.$token.'.'.$base;
            if (! $this->domainExistsInSystem($candidate)) {
                return $candidate;
            }
        }

        $candidate = 't-'.Str::lower(Str::random(12)).'.'.$base;
        if ($this->domainExistsInSystem($candidate)) {
            return 't-'.(string) Str::uuid().'.'.$base;
        }

        return $candidate;
    }

    private function domainExistsInSystem(string $domain): bool
    {
        $lower = mb_strtolower($domain);

        $inHostings = Hosting::query()
            ->whereRaw('LOWER(domain) = ?', [$lower])
            ->exists();

        $inHostDomains = HostDomain::query()
            ->whereRaw('LOWER(domain) = ?', [$lower])
            ->exists();

        return $inHostings || $inHostDomains;
    }

    private function belongsToHosting(Hosting $hosting, string $domain): bool
    {
        if (mb_strtolower($domain) === mb_strtolower($hosting->siteHost())) {
            return true;
        }

        return HostDomain::query()
            ->where('hosting_id', $hosting->id)
            ->whereRaw('LOWER(domain) = ?', [mb_strtolower($domain)])
            ->exists();
    }
}
