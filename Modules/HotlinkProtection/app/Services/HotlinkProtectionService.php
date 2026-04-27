<?php

namespace Modules\HotlinkProtection\Services;

use App\Models\Hosting;

class HotlinkProtectionService
{
    /**
     * @return array{
     *   enabled: bool,
     *   allowDirect: bool,
     *   allowedDomains: array<int, string>,
     *   blockedExtensions: array<int, string>,
     *   nginxSnippet: string
     * }
     */
    public function getConfig(Hosting $hosting): array
    {
        $enabled = (bool) ($hosting->hotlink_protection_enabled ?? false);
        $allowDirect = (bool) ($hosting->hotlink_allow_direct_requests ?? true);

        $allowedDomains = $this->normalizeDomainList((array) ($hosting->hotlink_allowed_domains ?? []), $hosting->siteHost());
        $blockedExtensions = $this->normalizeExtensions((array) ($hosting->hotlink_blocked_extensions ?? []));

        return [
            'enabled' => $enabled,
            'allowDirect' => $allowDirect,
            'allowedDomains' => $allowedDomains,
            'blockedExtensions' => $blockedExtensions,
            'nginxSnippet' => $this->buildNginxSnippet($allowedDomains, $blockedExtensions, $allowDirect),
        ];
    }

    /**
     * @param array{enabled: bool, allow_direct_requests: bool, allowed_domains_raw: string, blocked_extensions_raw: string} $input
     * @return array{ok: bool, errors?: array<string, string>, message?: string}
     */
    public function saveConfig(Hosting $hosting, array $input): array
    {
        $allowedDomains = $this->parseDomainsFromTextarea((string) $input['allowed_domains_raw']);
        $blockedExtensions = $this->parseExtensionsFromTextarea((string) $input['blocked_extensions_raw']);

        if ($allowedDomains === []) {
            return [
                'ok' => false,
                'errors' => ['allowed_domains' => 'Add at least one allowed domain.'],
            ];
        }
        if ($blockedExtensions === []) {
            return [
                'ok' => false,
                'errors' => ['blocked_extensions' => 'Add at least one protected extension.'],
            ];
        }

        $hosting->forceFill([
            'hotlink_protection_enabled' => (bool) $input['enabled'],
            'hotlink_allow_direct_requests' => (bool) $input['allow_direct_requests'],
            'hotlink_allowed_domains' => $allowedDomains,
            'hotlink_blocked_extensions' => $blockedExtensions,
        ])->save();

        return [
            'ok' => true,
            'message' => 'Hotlink protection settings saved.',
        ];
    }

    /**
     * @param array<int, mixed> $domains
     * @return array<int, string>
     */
    private function normalizeDomainList(array $domains, string $siteHost): array
    {
        $normalized = [];
        foreach ($domains as $raw) {
            $name = Hosting::normalizeDomainName((string) $raw);
            if ($name !== '') {
                $normalized[] = mb_strtolower($name);
            }
        }
        if ($siteHost !== '') {
            $normalized[] = mb_strtolower($siteHost);
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param array<int, mixed> $extensions
     * @return array<int, string>
     */
    private function normalizeExtensions(array $extensions): array
    {
        $out = [];
        foreach ($extensions as $raw) {
            $one = ltrim(trim((string) $raw), '.');
            if ($one === '') {
                continue;
            }
            $one = mb_strtolower($one);
            if (! preg_match('/^[a-z0-9]{1,10}$/', $one)) {
                continue;
            }
            $out[] = $one;
        }

        return array_values(array_unique($out));
    }

    /**
     * @return array<int, string>
     */
    private function parseDomainsFromTextarea(string $raw): array
    {
        $parts = preg_split('/[\s,]+/', trim($raw)) ?: [];
        $domains = [];
        foreach ($parts as $part) {
            $normalized = Hosting::normalizeDomainName((string) $part);
            if ($normalized === '') {
                continue;
            }
            $domains[] = mb_strtolower($normalized);
        }

        return array_values(array_unique($domains));
    }

    /**
     * @return array<int, string>
     */
    private function parseExtensionsFromTextarea(string $raw): array
    {
        $parts = preg_split('/[\s,]+/', trim($raw)) ?: [];

        return $this->normalizeExtensions($parts);
    }

    /**
     * This snippet can be pasted into host-level Nginx include/server block.
     */
    private function buildNginxSnippet(array $allowedDomains, array $blockedExtensions, bool $allowDirect): string
    {
        $extRegex = implode('|', array_map(static fn (string $e): string => preg_quote($e, '/'), $blockedExtensions));
        $allowedRegexParts = array_map(
            static fn (string $d): string => preg_quote($d, '/'),
            array_values(array_unique($allowedDomains))
        );
        $allowedRegex = implode('|', $allowedRegexParts);

        $cond = $allowDirect
            ? '$http_referer !~* "^$|https?://([^/]+\\.)?('.$allowedRegex.')(/|$)"'
            : '$http_referer !~* "^https?://([^/]+\\.)?('.$allowedRegex.')(/|$)"';

        return "location ~* \\.(".$extRegex.")$ {\n"
            ."    if (".$cond.") {\n"
            ."        return 403;\n"
            ."    }\n"
            ."}";
    }
}
