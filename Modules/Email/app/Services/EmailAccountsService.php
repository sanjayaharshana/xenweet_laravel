<?php

namespace Modules\Email\Services;

use App\Models\Hosting;
use Modules\Email\Models\HostEmailAutoresponder;
use Modules\Email\Models\HostEmailAccount;
use Modules\Email\Models\HostEmailFilter;
use Modules\Email\Models\HostEmailForwarder;

class EmailAccountsService
{
    public function listAccounts(Hosting $hosting): array
    {
        $accounts = HostEmailAccount::query()
            ->where('hosting_id', $hosting->id)
            ->orderByDesc('id')
            ->get();
        $forwarders = HostEmailForwarder::query()
            ->where('hosting_id', $hosting->id)
            ->orderByDesc('id')
            ->get();
        $autoresponders = HostEmailAutoresponder::query()
            ->where('hosting_id', $hosting->id)
            ->orderByDesc('id')
            ->get();
        $filters = HostEmailFilter::query()
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
            'forwarders' => $forwarders,
            'autoresponders' => $autoresponders,
            'filters' => $filters,
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

    public function createForwarder(Hosting $hosting, array $validated): array
    {
        $source = mb_strtolower(trim((string) $validated['source_email']));
        $destination = mb_strtolower(trim((string) $validated['destination_email']));
        if (! $this->belongsToHost($hosting, $source)) {
            return ['ok' => false, 'errors' => ['source_email' => 'Source email must belong to this hosting account.']];
        }

        $exists = HostEmailForwarder::query()
            ->where('hosting_id', $hosting->id)
            ->whereRaw('LOWER(source_email) = ?', [$source])
            ->whereRaw('LOWER(destination_email) = ?', [$destination])
            ->exists();
        if ($exists) {
            return ['ok' => false, 'errors' => ['source_email' => 'This forwarder already exists.']];
        }

        HostEmailForwarder::query()->create([
            'hosting_id' => $hosting->id,
            'source_email' => $source,
            'destination_email' => $destination,
            'status' => 'active',
        ]);

        return ['ok' => true, 'source' => $source, 'destination' => $destination];
    }

    public function removeForwarder(Hosting $hosting, HostEmailForwarder $forwarder): array
    {
        if ((int) $forwarder->hosting_id !== (int) $hosting->id) {
            return ['ok' => false, 'error' => 'Selected forwarder does not belong to this host.'];
        }

        $source = (string) $forwarder->source_email;
        $destination = (string) $forwarder->destination_email;
        $forwarder->delete();

        return ['ok' => true, 'source' => $source, 'destination' => $destination];
    }

    public function createAutoresponder(Hosting $hosting, array $validated): array
    {
        $email = mb_strtolower(trim((string) $validated['email']));
        if (! $this->belongsToHost($hosting, $email)) {
            return ['ok' => false, 'errors' => ['email' => 'Auto responder email must belong to this hosting account.']];
        }

        $exists = HostEmailAutoresponder::query()
            ->where('hosting_id', $hosting->id)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->exists();
        if ($exists) {
            return ['ok' => false, 'errors' => ['email' => 'Auto responder already exists for this mailbox.']];
        }

        HostEmailAutoresponder::query()->create([
            'hosting_id' => $hosting->id,
            'email' => $email,
            'subject' => trim((string) $validated['subject']),
            'body' => trim((string) $validated['body']),
            'enabled' => (bool) ($validated['enabled'] ?? false),
        ]);

        return ['ok' => true, 'email' => $email];
    }

    public function removeAutoresponder(Hosting $hosting, HostEmailAutoresponder $autoresponder): array
    {
        if ((int) $autoresponder->hosting_id !== (int) $hosting->id) {
            return ['ok' => false, 'error' => 'Selected auto responder does not belong to this host.'];
        }

        $email = (string) $autoresponder->email;
        $autoresponder->delete();

        return ['ok' => true, 'email' => $email];
    }

    public function createFilter(Hosting $hosting, array $validated): array
    {
        $scope = (string) $validated['scope'];
        $email = $scope === 'mailbox' ? mb_strtolower(trim((string) ($validated['email'] ?? ''))) : null;
        if ($scope === 'mailbox' && (! $email || ! $this->belongsToHost($hosting, $email))) {
            return ['ok' => false, 'errors' => ['email' => 'Mailbox filter must target an email account of this host.']];
        }

        $ruleName = trim((string) $validated['rule_name']);
        $exists = HostEmailFilter::query()
            ->where('hosting_id', $hosting->id)
            ->whereRaw('LOWER(rule_name) = ?', [mb_strtolower($ruleName)])
            ->exists();
        if ($exists) {
            return ['ok' => false, 'errors' => ['rule_name' => 'Filter name already exists for this host.']];
        }

        HostEmailFilter::query()->create([
            'hosting_id' => $hosting->id,
            'scope' => $scope,
            'email' => $email,
            'rule_name' => $ruleName,
            'condition_type' => (string) $validated['condition_type'],
            'condition_value' => trim((string) $validated['condition_value']),
            'action_type' => (string) $validated['action_type'],
            'action_value' => trim((string) ($validated['action_value'] ?? '')),
            'enabled' => true,
        ]);

        return ['ok' => true, 'name' => $ruleName];
    }

    public function removeFilter(Hosting $hosting, HostEmailFilter $filter): array
    {
        if ((int) $filter->hosting_id !== (int) $hosting->id) {
            return ['ok' => false, 'error' => 'Selected filter does not belong to this host.'];
        }

        $name = (string) $filter->rule_name;
        $filter->delete();

        return ['ok' => true, 'name' => $name];
    }

    private function isValidDomain(string $domain): bool
    {
        return (bool) preg_match(
            '/^(?=.{1,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)(?:\.([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?))*$/i',
            $domain
        );
    }

    private function belongsToHost(Hosting $hosting, string $email): bool
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, null);
        if (! $local || ! $domain) {
            return false;
        }

        return HostEmailAccount::query()
            ->where('hosting_id', $hosting->id)
            ->whereRaw('LOWER(local_part) = ?', [mb_strtolower($local)])
            ->whereRaw('LOWER(domain) = ?', [mb_strtolower($domain)])
            ->exists();
    }
}
