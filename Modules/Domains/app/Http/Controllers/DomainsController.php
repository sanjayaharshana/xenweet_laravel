<?php

namespace Modules\Domains\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Domains\Models\HostDomain;

class DomainsController extends Controller
{
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

        $validated = $request->validate([
            'domain_type' => ['required', 'string', Rule::in(['temporary', 'registered'])],
            'domain_name' => ['required_if:domain_type,registered', 'nullable', 'string', 'max:255'],
            'share_document_root' => ['nullable', 'boolean'],
            '_context' => ['nullable', 'string', 'in:add_domain'],
        ]);

        $type = $validated['domain_type'];
        $share = $request->boolean('share_document_root');

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

        HostDomain::query()->create([
            'hosting_id' => $hosting->id,
            'type' => $type,
            'domain' => $name,
            'share_document_root' => $share,
        ]);

        return redirect()
            ->route('hosts.domains.index', $hosting)
            ->with('success', 'Domain added: '.$name);
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
