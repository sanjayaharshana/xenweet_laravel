<?php

namespace Modules\ZeeBrooMail\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hosting;
use Illuminate\Http\JsonResponse;
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
        $state = $this->mailboxState($request, $hosting, $service);

        return view('zeebroomail::index', [
            'hosting' => $hosting,
            'accounts' => $state['accounts'],
            'selectedAccountId' => $state['selectedAccountId'],
            'folder' => $state['folder'],
            'foldersResult' => $state['foldersResult'],
            'mailboxResult' => $state['mailboxResult'],
            'messageResult' => null,
            'emailModuleEnabled' => Module::isEnabled('Email'),
        ]);
    }

    public function show(Request $request, Hosting $hosting, int $uid, ZeeBrooMailService $service): View
    {
        $state = $this->mailboxState($request, $hosting, $service);
        $messageResult = ['ok' => false, 'error' => 'No message selected.', 'message' => null];

        if ($state['selectedAccountId'] > 0) {
            $messageResult = $service->getMessage($hosting, $state['selectedAccountId'], $state['folder'], $uid);
        }

        return view('zeebroomail::index', [
            'hosting' => $hosting,
            'accounts' => $state['accounts'],
            'selectedAccountId' => $state['selectedAccountId'],
            'folder' => $state['folder'],
            'foldersResult' => $state['foldersResult'],
            'mailboxResult' => $state['mailboxResult'],
            'messageResult' => $messageResult,
            'emailModuleEnabled' => Module::isEnabled('Email'),
        ]);
    }

    public function mailboxData(Request $request, Hosting $hosting, ZeeBrooMailService $service): JsonResponse
    {
        $state = $this->mailboxState($request, $hosting, $service);

        return response()->json([
            'ok' => true,
            'selected_account_id' => $state['selectedAccountId'],
            'folder' => $state['folder'],
            'folders_result' => $state['foldersResult'],
            'mailbox_result' => $state['mailboxResult'],
        ]);
    }

    public function messageData(Request $request, Hosting $hosting, int $uid, ZeeBrooMailService $service): JsonResponse
    {
        $state = $this->mailboxState($request, $hosting, $service);
        $messageResult = ['ok' => false, 'error' => 'No message selected.', 'message' => null];

        if ($state['selectedAccountId'] > 0) {
            $messageResult = $service->getMessage($hosting, $state['selectedAccountId'], $state['folder'], $uid);
        }

        return response()->json([
            'ok' => (bool) ($messageResult['ok'] ?? false),
            'selected_account_id' => $state['selectedAccountId'],
            'folder' => $state['folder'],
            'message_result' => $messageResult,
        ]);
    }

    public function send(Request $request, Hosting $hosting, ZeeBrooMailService $service): RedirectResponse
    {
        $validated = $request->validate([
            'from_account_id' => ['required', 'integer', 'min:1'],
            'to' => ['required', 'string', 'max:1000'],
            'cc' => ['nullable', 'string', 'max:1000'],
            'bcc' => ['nullable', 'string', 'max:1000'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            '_context' => ['nullable', 'string', Rule::in(['send_email'])],
        ]);

        $toRecipients = $this->parseRecipients((string) $validated['to']);
        $ccRecipients = $this->parseRecipients((string) ($validated['cc'] ?? ''));
        $bccRecipients = $this->parseRecipients((string) ($validated['bcc'] ?? ''));

        if ($toRecipients === []) {
            return redirect()
                ->route('hosts.zeebroo-mail.index', ['hosting' => $hosting, 'account_id' => $validated['from_account_id']])
                ->withErrors(['zeebroo_mail' => 'Recipient email is required and must be valid.'])
                ->withInput();
        }

        $result = $service->sendMessage(
            $hosting,
            (int) $validated['from_account_id'],
            $toRecipients,
            $ccRecipients,
            $bccRecipients,
            (string) ($validated['subject'] ?? ''),
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

    public function sendAjax(Request $request, Hosting $hosting, ZeeBrooMailService $service): JsonResponse
    {
        $validated = $request->validate([
            'from_account_id' => ['required', 'integer', 'min:1'],
            'to' => ['required', 'string', 'max:1000'],
            'cc' => ['nullable', 'string', 'max:1000'],
            'bcc' => ['nullable', 'string', 'max:1000'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            '_context' => ['nullable', 'string', Rule::in(['send_email'])],
        ]);

        $toRecipients = $this->parseRecipients((string) $validated['to']);
        $ccRecipients = $this->parseRecipients((string) ($validated['cc'] ?? ''));
        $bccRecipients = $this->parseRecipients((string) ($validated['bcc'] ?? ''));

        if ($toRecipients === []) {
            return response()->json([
                'ok' => false,
                'error' => 'Recipient email is required and must be valid.',
            ], 422);
        }

        $result = $service->sendMessage(
            $hosting,
            (int) $validated['from_account_id'],
            $toRecipients,
            $ccRecipients,
            $bccRecipients,
            (string) ($validated['subject'] ?? ''),
            (string) $validated['body'],
        );

        if (! $result['ok']) {
            return response()->json([
                'ok' => false,
                'error' => (string) ($result['error'] ?? 'Failed to send email.'),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Email sent successfully.',
        ]);
    }

    private function mailboxState(Request $request, Hosting $hosting, ZeeBrooMailService $service): array
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

        return [
            'accounts' => $accounts,
            'selectedAccountId' => $selectedAccountId,
            'folder' => $folder,
            'foldersResult' => $foldersResult,
            'mailboxResult' => $mailboxResult,
        ];
    }

    private function parseRecipients(string $raw): array
    {
        $items = collect(preg_split('/[,\n;]+/', $raw) ?: [])
            ->map(fn ($item) => mb_strtolower(trim((string) $item)))
            ->filter()
            ->unique()
            ->values();

        return $items
            ->filter(fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->values()
            ->all();
    }
}
