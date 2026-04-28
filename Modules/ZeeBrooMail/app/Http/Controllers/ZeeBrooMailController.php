<?php

namespace Modules\ZeeBrooMail\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\ZeeBrooMail\Services\ZeeBrooMailService;
use Nwidart\Modules\Facades\Module;

class ZeeBrooMailController extends Controller
{
    public function index(Request $request, Hosting $hosting, ZeeBrooMailService $service): View
    {
        $accounts = $service->accountsForHosting($hosting);
        $selectedAccountId = (int) $request->query('account_id', (int) ($accounts->first()->id ?? 0));
        $folder = (string) $request->query('folder', 'INBOX');
        $foldersResult = ['ok' => false, 'error' => null, 'folders' => ['INBOX']];
        $mailboxResult = ['ok' => false, 'error' => null, 'messages' => []];

        if ($selectedAccountId > 0) {
            $foldersResult = $service->listFolders($hosting, $selectedAccountId);
            if ($foldersResult['ok'] && ! in_array($folder, $foldersResult['folders'], true)) {
                $folder = (string) ($foldersResult['folders'][0] ?? 'INBOX');
            }
            $mailboxResult = $service->listMessages($hosting, $selectedAccountId, $folder);
        }

        return view('zeebroomail::index', [
            'hosting' => $hosting,
            'accounts' => $accounts,
            'selectedAccountId' => $selectedAccountId,
            'folder' => $folder,
            'foldersResult' => $foldersResult,
            'mailboxResult' => $mailboxResult,
            'messageResult' => null,
            'emailModuleEnabled' => Module::isEnabled('Email'),
        ]);
    }

    public function show(Request $request, Hosting $hosting, int $uid, ZeeBrooMailService $service): View
    {
        $accounts = $service->accountsForHosting($hosting);
        $selectedAccountId = (int) $request->query('account_id', (int) ($accounts->first()->id ?? 0));
        $folder = (string) $request->query('folder', 'INBOX');
        $foldersResult = ['ok' => false, 'error' => null, 'folders' => ['INBOX']];
        $mailboxResult = ['ok' => false, 'error' => null, 'messages' => []];
        $messageResult = ['ok' => false, 'error' => 'No message selected.', 'message' => null];

        if ($selectedAccountId > 0) {
            $foldersResult = $service->listFolders($hosting, $selectedAccountId);
            if ($foldersResult['ok'] && ! in_array($folder, $foldersResult['folders'], true)) {
                $folder = (string) ($foldersResult['folders'][0] ?? 'INBOX');
            }
            $mailboxResult = $service->listMessages($hosting, $selectedAccountId, $folder);
            $messageResult = $service->getMessage($hosting, $selectedAccountId, $folder, $uid);
        }

        return view('zeebroomail::index', [
            'hosting' => $hosting,
            'accounts' => $accounts,
            'selectedAccountId' => $selectedAccountId,
            'folder' => $folder,
            'foldersResult' => $foldersResult,
            'mailboxResult' => $mailboxResult,
            'messageResult' => $messageResult,
            'emailModuleEnabled' => Module::isEnabled('Email'),
        ]);
    }

    public function send(Request $request, Hosting $hosting, ZeeBrooMailService $service): RedirectResponse
    {
        $validated = $request->validate([
            'from_account_id' => ['required', 'integer', 'min:1'],
            'to' => ['required', 'email:rfc,dns', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            '_context' => ['nullable', 'string', Rule::in(['send_email'])],
        ]);

        $result = $service->sendMessage(
            $hosting,
            (int) $validated['from_account_id'],
            (string) $validated['to'],
            (string) $validated['subject'],
            (string) $validated['body'],
        );

        if (! $result['ok']) {
            return redirect()
                ->route('hosts.zeebroo-mail.index', ['hosting' => $hosting, 'account_id' => $validated['from_account_id']])
                ->withErrors(['zeebroo_mail' => (string) ($result['error'] ?? 'Failed to send email.')])
                ->withInput();
        }

        return redirect()
            ->route('hosts.zeebroo-mail.index', ['hosting' => $hosting, 'account_id' => $validated['from_account_id']])
            ->with('success', 'Email sent successfully.');
    }
}
