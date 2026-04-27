<?php

namespace Modules\Email\Services;

use App\Models\Hosting;
use Modules\Email\Models\HostEmailAccount;

class EmailAccountsService
{
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

    private function isValidDomain(string $domain): bool
    {
        return (bool) preg_match(
            '/^(?=.{1,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)(?:\.([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?))*$/i',
            $domain
        );
    }
}
