<?php

namespace Modules\Domains\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hosting;
use App\Services\HostingCliProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Domains\Models\HostDomain;

class DomainsController extends Controller
{
    public function __construct(
        private readonly HostingCliProvisioner $provisioner
    ) {}

    public function index(Hosting $hosting): View
    {
        $hostDomains = collect();
        if (class_exists(HostDomain::class) && Schema::hasTable('host_domains')) {
            $hostDomains = HostDomain::query()
                ->where('hosting_id', $hosting->id)
                ->orderByDesc('id')
                ->get();
        }

        return view('domains::index', [
            'hosting' => $hosting,
            'hostDomains' => $hostDomains,
        ]);
    }

    public function store(Request $request, Hosting $hosting): RedirectResponse
    {
        if (! Schema::hasTable('host_domains')) {
            return redirect()
                ->route('hosts.domains.index', $hosting)
                ->with('error', 'Database is not ready. Run migrations: php artisan migrate');
        }
        $hasDocumentRootColumn = Schema::hasColumn('host_domains', 'document_root');

        $validated = $request->validate([
            'domain_type' => ['required', 'string', Rule::in(['temporary', 'registered'])],
            'domain_name' => ['required_if:domain_type,registered', 'nullable', 'string', 'max:255'],
            'root_mode' => ['nullable', 'string', Rule::in(['shared', 'custom'])],
            'document_root' => ['nullable', 'string', 'max:2048'],
            '_context' => ['nullable', 'string', 'in:add_domain'],
        ]);

        $type = $validated['domain_type'];
        $rootMode = (string) ($validated['root_mode'] ?? 'shared');
        $documentRoot = trim((string) ($validated['document_root'] ?? ''));
        if ($rootMode !== 'custom') {
            $documentRoot = '';
        }
        if ($rootMode === 'custom' && $documentRoot === '') {
            return redirect()
                ->route('hosts.domains.index', $hosting)
                ->withErrors(['document_root' => 'Please enter a custom root path.'])
                ->withInput();
        }
        if ($rootMode === 'custom' && ! str_starts_with($documentRoot, '/')) {
            return redirect()
                ->route('hosts.domains.index', $hosting)
                ->withErrors(['document_root' => 'Root path must start with "/" (absolute path).'])
                ->withInput();
        }
        $share = $rootMode !== 'custom';

        if ($type === 'registered') {
            $name = Hosting::normalizeDomainName((string) ($validated['domain_name'] ?? ''));
            if ($name === '') {
                return redirect()
                    ->route('hosts.domains.index', $hosting)
                    ->withErrors(['domain_name' => 'Please enter a valid domain.'])
                    ->withInput();
            }
        } else {
            $name = $this->generateTemporaryDomain($hosting);
        }

        if (strcasecmp($name, $hosting->siteHost()) === 0) {
            return redirect()
                ->route('hosts.domains.index', $hosting)
                ->withErrors(['domain_name' => 'This domain is already the primary domain for this account.'])
                ->withInput();
        }

        if ($this->domainExistsInSystem($name)) {
            $message = 'This domain is already in use.';

            return redirect()
                ->route('hosts.domains.index', $hosting)
                ->withErrors(['domain_name' => $message])
                ->withInput();
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
            return redirect()
                ->route('hosts.domains.index', $hosting)
                ->with('success', 'Domain saved: '.$name)
                ->with('error', 'Nginx was not updated so the new hostname may not be served yet: '.$vhost['message']);
        }

        $msg = 'Domain added: '.$name.'. '.trim((string) ($vhost['message'] ?? ''), '.');

        return redirect()
            ->route('hosts.domains.index', $hosting)
            ->with('success', $msg);
    }

    public function destroy(Hosting $hosting, HostDomain $hostDomain): RedirectResponse
    {
        if (! Schema::hasTable('host_domains')) {
            return redirect()
                ->route('hosts.domains.index', $hosting)
                ->with('error', 'Database is not ready. Run migrations: php artisan migrate');
        }

        if ((int) $hostDomain->hosting_id !== (int) $hosting->id) {
            return redirect()
                ->route('hosts.domains.index', $hosting)
                ->with('error', 'The selected domain does not belong to this hosting account.');
        }

        $name = (string) $hostDomain->domain;
        $hostDomain->delete();

        $vhost = $this->provisioner->reapplyWebVhost($hosting->refresh());
        if (! $vhost['success']) {
            return redirect()
                ->route('hosts.domains.index', $hosting)
                ->with('success', 'Domain removed: '.$name)
                ->with('error', 'Nginx was not updated after delete: '.$vhost['message']);
        }

        $msg = 'Domain removed: '.$name.'. '.trim((string) ($vhost['message'] ?? ''), '.');

        return redirect()
            ->route('hosts.domains.index', $hosting)
            ->with('success', $msg);
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
}
