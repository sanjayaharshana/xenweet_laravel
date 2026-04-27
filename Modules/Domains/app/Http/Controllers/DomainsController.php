<?php

namespace Modules\Domains\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Domains\Models\HostDomain;
use Modules\Domains\Models\HostDomainRedirect;
use Modules\Domains\Models\HostDomainZoneRecord;
use Modules\Domains\Services\DomainsApplicationService;

class DomainsController extends Controller
{
    public function index(Hosting $hosting, Request $request, DomainsApplicationService $service): View
    {
        $data = $service->buildIndexData(
            $hosting,
            (string) $request->query('tab', ''),
            $request->query('filter_zone'),
            $request->boolean('refresh_dns')
        );

        return view('domains::index', [
            'hosting' => $hosting,
            ...$data,
        ]);
    }

    public function store(Request $request, Hosting $hosting, DomainsApplicationService $service): RedirectResponse
    {
        $validated = $request->validate([
            'domain_type' => ['required', 'string', Rule::in(['temporary', 'registered'])],
            'domain_name' => ['required_if:domain_type,registered', 'nullable', 'string', 'max:255'],
            'root_mode' => ['nullable', 'string', Rule::in(['shared', 'custom'])],
            'document_root' => ['nullable', 'string', 'max:2048'],
            '_context' => ['nullable', 'string', 'in:add_domain'],
        ]);

        return $this->toRedirect($service->storeDomain($hosting, $validated), route('hosts.domains.index', $hosting), true);
    }

    public function destroy(Hosting $hosting, HostDomain $hostDomain, DomainsApplicationService $service): RedirectResponse
    {
        return $this->toRedirect($service->destroyDomain($hosting, $hostDomain), route('hosts.domains.index', $hosting));
    }

    public function storeRedirect(Request $request, Hosting $hosting, DomainsApplicationService $service): RedirectResponse
    {
        $validated = $request->validate([
            'source_domain' => ['required', 'string', 'max:255'],
            'redirect_type' => ['required', 'string', Rule::in(['temporary', 'permanent'])],
            'redirect_url' => ['required', 'url', 'max:2048'],
            '_context' => ['nullable', 'string', 'in:add_redirect'],
        ]);

        return $this->toRedirect(
            $service->storeRedirect($hosting, $validated),
            route('hosts.domains.index', ['hosting' => $hosting, 'tab' => 'redirects']),
            true
        );
    }

    public function destroyRedirect(Hosting $hosting, HostDomainRedirect $redirect, DomainsApplicationService $service): RedirectResponse
    {
        return $this->toRedirect(
            $service->destroyRedirect($hosting, $redirect),
            route('hosts.domains.index', ['hosting' => $hosting, 'tab' => 'redirects'])
        );
    }

    public function storeZoneRecord(Request $request, Hosting $hosting, DomainsApplicationService $service): RedirectResponse
    {
        $validated = $request->validate([
            'zone_domain' => ['required', 'string', 'max:255'],
            'record_name' => ['required', 'string', 'max:255'],
            'record_type' => ['required', 'string', Rule::in(['A', 'AAAA', 'CNAME', 'MX', 'TXT'])],
            'record_value' => ['required', 'string', 'max:2000'],
            'mx_priority' => ['required_if:record_type,MX', 'nullable', 'integer', 'min:0', 'max:65535'],
            'ttl' => ['nullable', 'integer', 'min:60', 'max:86400'],
            '_context' => ['nullable', 'string', 'in:add_zone'],
        ], [], [
            'zone_domain' => 'zone',
            'record_name' => 'name',
            'record_type' => 'type',
            'record_value' => 'value',
        ]);

        return $this->toRedirect(
            $service->storeZoneRecord($hosting, $validated),
            route('hosts.domains.index', array_filter([
                'hosting' => $hosting,
                'tab' => 'zone',
                'filter_zone' => $service->normalizedFilterZone($hosting, $request->input('return_filter_zone')),
            ], fn ($v) => $v !== null && $v !== '')),
            true
        );
    }

    public function destroyZoneRecord(Request $request, Hosting $hosting, HostDomainZoneRecord $zoneRecord, DomainsApplicationService $service): RedirectResponse
    {
        return $this->toRedirect(
            $service->destroyZoneRecord($hosting, $zoneRecord),
            route('hosts.domains.index', array_filter([
                'hosting' => $hosting,
                'tab' => 'zone',
                'filter_zone' => $service->normalizedFilterZone($hosting, $request->input('return_filter_zone')),
            ], fn ($v) => $v !== null && $v !== ''))
        );
    }

    private function toRedirect(array $result, string $route, bool $withInputOnValidation = false): RedirectResponse
    {
        $redirect = redirect()->to($route);
        $type = (string) ($result['type'] ?? 'error');

        if ($type === 'validation') {
            $errors = (array) ($result['errors'] ?? ['action' => 'Validation failed.']);
            if ($withInputOnValidation) {
                return $redirect->withErrors($errors)->withInput();
            }

            return $redirect->withErrors($errors);
        }

        if (! empty($result['success'])) {
            $redirect = $redirect->with('success', (string) $result['success']);
        }
        if (! empty($result['error'])) {
            $redirect = $redirect->with('error', (string) $result['error']);
        }

        if (! empty($result['message']) && empty($result['error']) && empty($result['success'])) {
            if ($type === 'success') {
                $redirect = $redirect->with('success', (string) $result['message']);
            } else {
                $redirect = $redirect->with('error', (string) $result['message']);
            }
        }

        return $redirect;
    }
}
