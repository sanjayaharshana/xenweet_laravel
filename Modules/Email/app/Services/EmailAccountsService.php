<?php

namespace Modules\Email\Services;

use App\Models\Hosting;
use App\Services\HostingCliProvisioner;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Modules\Email\Models\HostEmailAccount;
use Throwable;

class EmailAccountsService
{
    public function __construct(
        private readonly HostingCliProvisioner $provisioner
    ) {
    }

    public function listAccounts(Hosting $hosting): array
    {
        $accounts = HostEmailAccount::query()
            ->where('hosting_id', $hosting->id)
            ->orderByDesc('id')
            ->get();
        $accounts->each(function (HostEmailAccount $account) use ($hosting): void {
            $changed = false;

            $local = strtolower(trim((string) $account->local_part));
            if (str_contains($local, '{{') || str_contains($local, '}}') || str_contains($local, '$account->domain')) {
                $local = str_replace(['{{', '}}', '$account->domain'], '', $local);
                $local = preg_replace('/\s+/', '', $local) ?? '';
                $local = preg_replace('/[^a-z0-9._%+\-]/', '', $local) ?? '';
                $local = trim($local, '.-');
                if ($local !== '' && $local !== (string) $account->local_part) {
                    $account->local_part = $local;
                    $changed = true;
                }
            }

            $domain = Hosting::normalizeDomainName((string) $account->domain);
            if (! $this->isValidDomain($domain)) {
                $fallback = $hosting->siteHost();
                if ($fallback !== '' && $fallback !== (string) $account->domain) {
                    $account->domain = $fallback;
                    $changed = true;
                }
            }

            if ($changed) {
                $account->save();
            }
        });

        return [
            'accounts' => $accounts,
            'defaultDomain' => $hosting->siteHost(),
        ];
    }

    public function createAccount(Hosting $hosting, array $validated): array
    {
        $domain = Hosting::normalizeDomainName((string) $validated['domain']);
        if ($domain === '') {
            return [
                'ok' => false,
                'errors' => ['domain' => 'Invalid email domain.'],
            ];
        }
        if (! $this->isValidDomain($domain)) {
            return [
                'ok' => false,
                'errors' => ['domain' => 'Domain must be a valid hostname (example.com).'],
            ];
        }

        $localPart = strtolower(trim((string) $validated['local_part']));
        if (! preg_match('/^[a-z0-9._%+\-]{1,64}$/', $localPart)) {
            return [
                'ok' => false,
                'errors' => ['local_part' => 'Invalid email username format.'],
            ];
        }

        $exists = HostEmailAccount::query()
            ->where('hosting_id', $hosting->id)
            ->whereRaw('LOWER(local_part) = ?', [mb_strtolower($localPart)])
            ->whereRaw('LOWER(domain) = ?', [mb_strtolower($domain)])
            ->exists();
        if ($exists) {
            return [
                'ok' => false,
                'errors' => ['local_part' => 'That email account already exists for this host.'],
            ];
        }

        $account = HostEmailAccount::query()->create([
            'hosting_id' => $hosting->id,
            'local_part' => $localPart,
            'domain' => $domain,
            'password' => (string) $validated['password'],
            'quota_mb' => (int) ($validated['quota_mb'] ?? 1024),
            'status' => 'active',
        ]);

        return [
            'ok' => true,
            'email' => $account->local_part.'@'.$account->domain,
        ];
    }

    public function removeAccount(Hosting $hosting, HostEmailAccount $account): array
    {
        if ((int) $account->hosting_id !== (int) $hosting->id) {
            return [
                'ok' => false,
                'error' => 'Selected email account does not belong to this host.',
            ];
        }

        $email = $account->local_part.'@'.$account->domain;
        $account->delete();

        return [
            'ok' => true,
            'email' => $email,
        ];
    }

    public function roundcubeActionUrl(Hosting $hosting): ?string
    {
        $mailSubdomainUrl = $this->deployedRoundcubeUrl($hosting);
        if ($mailSubdomainUrl !== null) {
            return $mailSubdomainUrl.'?_task=login';
        }

        $override = trim((string) env('EMAIL_ROUNDCUBE_URL', ''));
        if ($override !== '') {
            return rtrim($override, '/').'?_task=login';
        }
        if (is_dir(public_path('roundcube'))) {
            return url('roundcube/?_task=login');
        }

        return null;
    }

    public function deployRoundcubeForHosting(Hosting $hosting): array
    {
        if (! class_exists(\Nwidart\Modules\Facades\Module::class)
            || ! \Nwidart\Modules\Facades\Module::isEnabled('Domains')
            || ! Schema::hasTable('host_domains')
            || ! class_exists('Modules\\Domains\\Models\\HostDomain')) {
            return [
                'ok' => false,
                'error' => 'Domains module is required to map mail.<domain> subdomain. Enable Domains module first.',
            ];
        }

        $sourceResult = $this->resolveRoundcubeSourceFromArchive();
        if (! $sourceResult['ok']) {
            return [
                'ok' => false,
                'error' => (string) $sourceResult['error'],
            ];
        }
        $source = (string) $sourceResult['path'];

        $hostRoot = trim((string) $hosting->host_root_path);
        if ($hostRoot === '' || ! is_dir($hostRoot)) {
            return [
                'ok' => false,
                'error' => 'Hosting root path is missing. Provision the hosting account first.',
            ];
        }

        $mailDomain = 'mail.'.$hosting->siteHost();
        $docRoot = rtrim($hostRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'mail'.DIRECTORY_SEPARATOR.'public_html';
        File::ensureDirectoryExists($docRoot);

        if (! File::exists($docRoot.DIRECTORY_SEPARATOR.'index.php')) {
            if (! File::copyDirectory($source, $docRoot)) {
                return [
                    'ok' => false,
                    'error' => 'Could not copy Roundcube files into the mail subdomain document root.',
                ];
            }
        }

        $this->ensureMailSubdomainDomainRecord($hosting, $mailDomain, $docRoot);

        $vhost = $this->provisioner->reapplyWebVhost($hosting->refresh());
        if (! $vhost['success']) {
            return [
                'ok' => false,
                'error' => 'Roundcube files deployed, but web server update failed: '.(string) $vhost['message'],
            ];
        }

        $scheme = (string) config('hosting.open_host_scheme', 'http');
        $target = $scheme.'://'.$mailDomain;

        return [
            'ok' => true,
            'message' => 'Roundcube deployed at '.$target.'. '.trim((string) ($vhost['message'] ?? '')),
        ];
    }

    private function deployedRoundcubeUrl(Hosting $hosting): ?string
    {
        if (! class_exists(\Nwidart\Modules\Facades\Module::class)
            || ! \Nwidart\Modules\Facades\Module::isEnabled('Domains')
            || ! Schema::hasTable('host_domains')) {
            return null;
        }

        $hostDomainClass = 'Modules\\Domains\\Models\\HostDomain';
        if (! class_exists($hostDomainClass)) {
            return null;
        }

        $mailDomain = 'mail.'.$hosting->siteHost();
        $exists = $hostDomainClass::query()
            ->where('hosting_id', $hosting->id)
            ->whereRaw('LOWER(domain) = ?', [mb_strtolower($mailDomain)])
            ->exists();

        if (! $exists) {
            return null;
        }

        $scheme = (string) config('hosting.open_host_scheme', 'http');

        return $scheme.'://'.$mailDomain.'/';
    }

    private function ensureMailSubdomainDomainRecord(Hosting $hosting, string $mailDomain, string $docRoot): void
    {
        if (! class_exists(\Nwidart\Modules\Facades\Module::class)
            || ! \Nwidart\Modules\Facades\Module::isEnabled('Domains')
            || ! Schema::hasTable('host_domains')) {
            return;
        }

        $hostDomainClass = 'Modules\\Domains\\Models\\HostDomain';
        if (! class_exists($hostDomainClass)) {
            return;
        }

        $existing = $hostDomainClass::query()
            ->where('hosting_id', $hosting->id)
            ->whereRaw('LOWER(domain) = ?', [mb_strtolower($mailDomain)])
            ->first();

        if ($existing !== null) {
            $existing->share_document_root = false;
            if (Schema::hasColumn('host_domains', 'document_root')) {
                $existing->document_root = $docRoot;
            }
            $existing->save();

            return;
        }

        $payload = [
            'hosting_id' => $hosting->id,
            'type' => 'registered',
            'domain' => $mailDomain,
            'share_document_root' => false,
        ];
        if (Schema::hasColumn('host_domains', 'document_root')) {
            $payload['document_root'] = $docRoot;
        }

        $hostDomainClass::query()->create($payload);
    }

    private function resolveRoundcubeSourceFromArchive(): array
    {
        $archivePath = public_path('roundcubemail-1.5.15-complete.tar.gz');
        if (! is_file($archivePath)) {
            return [
                'ok' => false,
                'error' => 'Roundcube archive not found at public/roundcubemail-1.5.15-complete.tar.gz.',
            ];
        }

        $extractBase = storage_path('app/roundcube-source');
        File::ensureDirectoryExists($extractBase);

        $extractedRoot = $extractBase.DIRECTORY_SEPARATOR.'roundcubemail-1.5.15';
        if (is_dir($extractedRoot) && is_file($extractedRoot.DIRECTORY_SEPARATOR.'index.php')) {
            return ['ok' => true, 'path' => $extractedRoot];
        }

        try {
            $tarPath = str_ends_with($archivePath, '.gz') ? substr($archivePath, 0, -3) : $archivePath.'.tar';
            if (! is_file((string) $tarPath)) {
                $gzip = new \PharData($archivePath);
                $gzip->decompress();
            }

            $tar = new \PharData((string) $tarPath);
            $tar->extractTo($extractBase, null, true);
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'error' => 'Could not extract Roundcube archive: '.$e->getMessage(),
            ];
        }

        if (! is_dir($extractedRoot) || ! is_file($extractedRoot.DIRECTORY_SEPARATOR.'index.php')) {
            return [
                'ok' => false,
                'error' => 'Roundcube archive extracted, but roundcubemail-1.5.15 source folder was not found.',
            ];
        }

        return ['ok' => true, 'path' => $extractedRoot];
    }

    private function isValidDomain(string $domain): bool
    {
        return (bool) preg_match(
            '/^(?=.{1,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)(?:\.([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?))*$/i',
            $domain
        );
    }
}
