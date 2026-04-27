<?php

namespace Modules\Email\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Email\Models\HostEmailAccount;
use Modules\Email\Services\EmailAccountsService;

class EmailAccountsController extends Controller
{
    public function index(Hosting $hosting, EmailAccountsService $service): View
    {
        return view('email::accounts', [
            'hosting' => $hosting,
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

}
