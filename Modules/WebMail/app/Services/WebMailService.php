<?php

namespace Modules\WebMail\Services;

use App\Models\Hosting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Nwidart\Modules\Facades\Module;

class WebMailService
{
    public function accountsForHosting(Hosting $hosting): Collection
    {
        if (! Module::isEnabled('Email') || ! class_exists(\Modules\Email\Models\HostEmailAccount::class)) {
            return collect();
        }

        return \Modules\Email\Models\HostEmailAccount::query()
            ->where('hosting_id', $hosting->id)
            ->orderBy('local_part')
            ->get();
    }

    public function listFolders(Hosting $hosting, int $accountId): array
    {
        $connection = $this->openMailboxConnection($hosting, $accountId, 'INBOX');
        if (! $connection['ok']) {
            return $connection;
        }

        $stream = $connection['stream'];
        $imapBase = $connection['imap_base'];
        $folders = imap_list($stream, $imapBase, '*');
        imap_close($stream);

        if (! is_array($folders)) {
            return [
                'ok' => false,
                'error' => (string) (imap_last_error() ?: 'Unable to fetch folders.'),
                'folders' => [],
            ];
        }

        $normalized = collect($folders)
            ->map(function (string $raw) use ($imapBase): string {
                $name = str_replace($imapBase, '', $raw);

                return str_replace(['INBOX.', 'INBOX/'], '', $name);
            })
            ->unique()
            ->values()
            ->all();

        return [
            'ok' => true,
            'error' => null,
            'folders' => $normalized,
        ];
    }

    public function listMessages(Hosting $hosting, int $accountId, string $folder = 'INBOX', int $limit = 25): array
    {
        $connection = $this->openMailboxConnection($hosting, $accountId, $folder);
        if (! $connection['ok']) {
            return $connection;
        }

        $stream = $connection['stream'];
        $account = $connection['account'];

        $total = (int) imap_num_msg($stream);
        $start = max(1, $total - $limit + 1);
        $messages = [];

        for ($index = $total; $index >= $start; $index--) {
            $overview = imap_fetch_overview($stream, (string) $index, 0);
            if (! isset($overview[0])) {
                continue;
            }

            $item = $overview[0];
            $messages[] = [
                'uid' => (int) ($item->uid ?? 0),
                'subject' => isset($item->subject) ? imap_utf8((string) $item->subject) : '(No subject)',
                'from' => isset($item->from) ? imap_utf8((string) $item->from) : '-',
                'to' => isset($item->to) ? imap_utf8((string) $item->to) : '-',
                'date' => (string) ($item->date ?? '-'),
                'seen' => (bool) ($item->seen ?? false),
            ];
        }

        imap_close($stream);

        return [
            'ok' => true,
            'error' => null,
            'messages' => $messages,
            'folder' => $folder,
            'selected_account' => $account,
        ];
    }

    public function getMessage(Hosting $hosting, int $accountId, string $folder, int $uid): array
    {
        $connection = $this->openMailboxConnection($hosting, $accountId, $folder);
        if (! $connection['ok']) {
            return $connection;
        }

        $stream = $connection['stream'];
        $messageNo = imap_msgno($stream, $uid);
        if ($messageNo <= 0) {
            imap_close($stream);

            return [
                'ok' => false,
                'error' => 'Message not found in selected folder.',
                'message' => null,
            ];
        }

        $overview = imap_fetch_overview($stream, (string) $messageNo, 0);
        $header = imap_headerinfo($stream, $messageNo);
        $rawBody = imap_fetchbody($stream, $messageNo, '1');
        if ($rawBody === '' || $rawBody === false) {
            $rawBody = imap_body($stream, $messageNo);
        }

        imap_setflag_full($stream, (string) $messageNo, '\\Seen');
        imap_close($stream);

        $subject = isset($overview[0]->subject) ? imap_utf8((string) $overview[0]->subject) : '(No subject)';
        $from = isset($overview[0]->from) ? imap_utf8((string) $overview[0]->from) : '-';
        $to = isset($overview[0]->to) ? imap_utf8((string) $overview[0]->to) : '-';
        $date = (string) ($overview[0]->date ?? '-');

        return [
            'ok' => true,
            'error' => null,
            'message' => [
                'uid' => $uid,
                'subject' => $subject,
                'from' => $from,
                'to' => $to,
                'date' => $date,
                'body' => $this->normalizeBodyForDisplay((string) $rawBody),
                'reply_to' => (string) ($header->reply_toaddress ?? $from),
            ],
        ];
    }

    public function sendMessage(Hosting $hosting, int $fromAccountId, string $to, string $subject, string $body): array
    {
        $account = $this->accountsForHosting($hosting)->firstWhere('id', $fromAccountId);
        if (! $account) {
            return ['ok' => false, 'error' => 'Selected sender mailbox is invalid.'];
        }

        $fromAddress = (string) $account->local_part.'@'.(string) $account->domain;
        $smtpHost = (string) Config::get('mail.mailers.smtp.host', $hosting->server_ip ?: '127.0.0.1');
        $smtpPort = (int) Config::get('mail.mailers.smtp.port', 587);
        $smtpEncryption = (string) Config::get('mail.mailers.smtp.encryption', 'tls');

        Config::set('mail.mailers.webmail_smtp', [
            'transport' => 'smtp',
            'host' => $smtpHost,
            'port' => $smtpPort,
            'encryption' => $smtpEncryption,
            'username' => $fromAddress,
            'password' => (string) $account->password,
            'timeout' => null,
            'local_domain' => null,
        ]);

        try {
            Mail::mailer('webmail_smtp')->raw($body, function ($message) use ($to, $subject, $fromAddress): void {
                $message->from($fromAddress)
                    ->to($to)
                    ->subject($subject);
            });
        } catch (\Throwable $throwable) {
            return ['ok' => false, 'error' => $throwable->getMessage()];
        }

        return ['ok' => true, 'error' => null];
    }

    private function openMailboxConnection(Hosting $hosting, int $accountId, string $folder): array
    {
        if (! function_exists('imap_open')) {
            return [
                'ok' => false,
                'error' => 'PHP IMAP extension is not installed on this server.',
                'messages' => [],
            ];
        }

        $account = $this->accountsForHosting($hosting)->firstWhere('id', $accountId);
        if (! $account) {
            return [
                'ok' => false,
                'error' => 'Selected mailbox is invalid for this host.',
                'messages' => [],
            ];
        }

        $username = (string) $account->local_part.'@'.(string) $account->domain;
        $password = (string) $account->password;
        $imapHost = (string) Config::get('mail.mailers.imap.host', $hosting->server_ip ?: '127.0.0.1');
        $imapPort = (int) Config::get('mail.mailers.imap.port', 993);
        $imapEncryption = (string) Config::get('mail.mailers.imap.encryption', 'ssl');
        $imapFlags = '/imap'.($imapEncryption !== '' ? '/'.$imapEncryption : '').'/novalidate-cert';
        $imapBase = '{'.$imapHost.':'.$imapPort.$imapFlags.'}';
        $mailbox = $imapBase.trim($folder);
        $stream = @imap_open($mailbox, $username, $password);

        if (! $stream) {
            return [
                'ok' => false,
                'error' => (string) (imap_last_error() ?: 'Unable to connect to mailbox.'),
                'messages' => [],
            ];
        }

        return [
            'ok' => true,
            'error' => null,
            'account' => $account,
            'stream' => $stream,
            'imap_base' => $imapBase,
        ];
    }

    private function normalizeBodyForDisplay(string $rawBody): string
    {
        $decoded = quoted_printable_decode($rawBody);
        $decoded = str_replace(["\r\n", "\r"], "\n", $decoded);

        return trim($decoded) !== '' ? $decoded : '(No message body available)';
    }
}
