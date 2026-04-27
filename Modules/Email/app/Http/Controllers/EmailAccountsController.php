<?php

namespace Modules\Email\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Email\Models\HostEmailAutoresponder;
use Modules\Email\Models\HostEmailAccount;
use Modules\Email\Models\HostEmailFilter;
use Modules\Email\Models\HostEmailForwarder;
use Modules\Email\Services\EmailAccountsService;

class EmailAccountsController extends Controller
{
    public function index(Hosting $hosting, EmailAccountsService $service): View
    {
        $tab = request()->query('tab', 'accounts');

        return view('email::accounts', [
            'hosting' => $hosting,
            'tab' => $tab,
            ...$service->listAccounts($hosting),
        ]);
    }

    public function store(Request $request, Hosting $hosting, EmailAccountsService $service): RedirectResponse
    {
        $validated = $request->validate([
            'local_part' => ['required', 'string', 'max:64'],
            'domain' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
            'quota_mb' => ['nullable', 'integer', 'min:50', 'max:1048576'],
            '_context' => ['nullable', 'string', Rule::in(['create_email'])],
        ]);

        $result = $service->createAccount($hosting, $validated);
        if (! $result['ok']) {
            return redirect()
                ->route('hosts.email.accounts', $hosting)
                ->withErrors($result['errors'] ?? ['email' => 'Unable to create email account.'])
                ->withInput();
        }

        return redirect()
            ->route('hosts.email.accounts', $hosting)
            ->with('success', 'Email account created: '.$result['email']);
    }

    public function destroy(Hosting $hosting, HostEmailAccount $emailAccount, EmailAccountsService $service): RedirectResponse
    {
        $result = $service->removeAccount($hosting, $emailAccount);
        if (! $result['ok']) {
            return redirect()
                ->route('hosts.email.accounts', $hosting)
                ->with('error', (string) ($result['error'] ?? 'Unable to delete email account.'));
        }

        return redirect()
            ->route('hosts.email.accounts', $hosting)
            ->with('success', 'Email account removed: '.$result['email']);
    }

    public function storeForwarder(Request $request, Hosting $hosting, EmailAccountsService $service): RedirectResponse
    {
        $validated = $request->validate([
            'source_email' => ['required', 'email:rfc,dns', 'max:255'],
            'destination_email' => ['required', 'email:rfc,dns', 'max:255'],
            '_context' => ['nullable', 'string', Rule::in(['create_forwarder'])],
        ]);

        $result = $service->createForwarder($hosting, $validated);
        if (! $result['ok']) {
            return redirect()
                ->route('hosts.email.index', ['hosting' => $hosting, 'tab' => 'forwarders'])
                ->withErrors($result['errors'] ?? ['forwarder' => 'Unable to create forwarder.'])
                ->withInput();
        }

        return redirect()
            ->route('hosts.email.index', ['hosting' => $hosting, 'tab' => 'forwarders'])
            ->with('success', 'Forwarder created: '.$result['source'].' -> '.$result['destination']);
    }

    public function destroyForwarder(Hosting $hosting, HostEmailForwarder $forwarder, EmailAccountsService $service): RedirectResponse
    {
        $result = $service->removeForwarder($hosting, $forwarder);
        if (! $result['ok']) {
            return redirect()
                ->route('hosts.email.index', ['hosting' => $hosting, 'tab' => 'forwarders'])
                ->with('error', (string) ($result['error'] ?? 'Unable to delete forwarder.'));
        }

        return redirect()
            ->route('hosts.email.index', ['hosting' => $hosting, 'tab' => 'forwarders'])
            ->with('success', 'Forwarder removed: '.$result['source'].' -> '.$result['destination']);
    }

    public function storeAutoresponder(Request $request, Hosting $hosting, EmailAccountsService $service): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc,dns', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:3000'],
            'enabled' => ['nullable', 'boolean'],
            '_context' => ['nullable', 'string', Rule::in(['create_autoresponder'])],
        ]);

        $result = $service->createAutoresponder($hosting, $validated);
        if (! $result['ok']) {
            return redirect()
                ->route('hosts.email.index', ['hosting' => $hosting, 'tab' => 'autoresponders'])
                ->withErrors($result['errors'] ?? ['autoresponder' => 'Unable to create auto responder.'])
                ->withInput();
        }

        return redirect()
            ->route('hosts.email.index', ['hosting' => $hosting, 'tab' => 'autoresponders'])
            ->with('success', 'Auto responder saved for: '.$result['email']);
    }

    public function destroyAutoresponder(Hosting $hosting, HostEmailAutoresponder $autoresponder, EmailAccountsService $service): RedirectResponse
    {
        $result = $service->removeAutoresponder($hosting, $autoresponder);
        if (! $result['ok']) {
            return redirect()
                ->route('hosts.email.index', ['hosting' => $hosting, 'tab' => 'autoresponders'])
                ->with('error', (string) ($result['error'] ?? 'Unable to delete auto responder.'));
        }

        return redirect()
            ->route('hosts.email.index', ['hosting' => $hosting, 'tab' => 'autoresponders'])
            ->with('success', 'Auto responder removed: '.$result['email']);
    }

    public function storeFilter(Request $request, Hosting $hosting, EmailAccountsService $service): RedirectResponse
    {
        $validated = $request->validate([
            'scope' => ['required', 'string', Rule::in(['global', 'mailbox'])],
            'email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'rule_name' => ['required', 'string', 'max:120'],
            'condition_type' => ['required', 'string', Rule::in(['contains', 'equals'])],
            'condition_value' => ['required', 'string', 'max:255'],
            'action_type' => ['required', 'string', Rule::in(['move_to_folder', 'discard', 'mark_read'])],
            'action_value' => ['nullable', 'string', 'max:255'],
            '_context' => ['nullable', 'string', Rule::in(['create_filter'])],
        ]);

        $result = $service->createFilter($hosting, $validated);
        if (! $result['ok']) {
            return redirect()
                ->route('hosts.email.index', ['hosting' => $hosting, 'tab' => 'filters'])
                ->withErrors($result['errors'] ?? ['filter' => 'Unable to create filter.'])
                ->withInput();
        }

        return redirect()
            ->route('hosts.email.index', ['hosting' => $hosting, 'tab' => 'filters'])
            ->with('success', 'Filter saved: '.$result['name']);
    }

    public function destroyFilter(Hosting $hosting, HostEmailFilter $filter, EmailAccountsService $service): RedirectResponse
    {
        $result = $service->removeFilter($hosting, $filter);
        if (! $result['ok']) {
            return redirect()
                ->route('hosts.email.index', ['hosting' => $hosting, 'tab' => 'filters'])
                ->with('error', (string) ($result['error'] ?? 'Unable to delete filter.'));
        }

        return redirect()
            ->route('hosts.email.index', ['hosting' => $hosting, 'tab' => 'filters'])
            ->with('success', 'Filter removed: '.$result['name']);
    }

    public function roundcubeAutoLogin(Hosting $hosting, HostEmailAccount $emailAccount): View|RedirectResponse
    {
        if ((int) $emailAccount->hosting_id !== (int) $hosting->id) {
            return redirect()
                ->route('hosts.email.index', ['hosting' => $hosting, 'tab' => 'accounts'])
                ->with('error', 'Selected email account does not belong to this host.');
        }

        $email = $emailAccount->local_part.'@'.$emailAccount->domain;
        $password = (string) $emailAccount->password;
        if ($password === '') {
            return redirect()
                ->route('hosts.email.index', ['hosting' => $hosting, 'tab' => 'accounts'])
                ->with('error', 'Cannot auto-login because mailbox password is empty.');
        }

        return view('email::roundcube-autologin', [
            'email' => $email,
            'password' => $password,
            'roundcubeBase' => url('/roundcube/'),
        ]);
    }
}
