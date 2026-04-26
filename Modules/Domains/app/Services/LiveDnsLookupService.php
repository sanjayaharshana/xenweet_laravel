<?php

namespace Modules\Domains\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Fetches public DNS as seen from this server (same resolvers the host uses: systemd-resolved, /etc/resolv.conf, etc.).
 * This is for display only — it does not change DNS at a registrar.
 */
final class LiveDnsLookupService
{
    private int $cacheTtlSeconds = 60;

    public function __construct() {}

    /**
     * @param  array<int, string>  $zoneDomains  Apex FQDNs, e.g. example.com
     * @return array{ok: bool, error: string|null, rows: Collection, fetched_at: Carbon|null}
     */
    public function fetchForZones(array $zoneDomains, bool $bustCache = false): array
    {
        if (! function_exists('dns_get_record')) {
            return [
                'ok' => false,
                'error' => 'This PHP build has no dns_get_record() function (disable_functions). Live DNS view is unavailable.',
                'rows' => collect(),
                'fetched_at' => null,
            ];
        }

        $zones = array_values(array_unique(array_filter(array_map('trim', $zoneDomains))));
        if ($zones === []) {
            return [
                'ok' => true,
                'error' => null,
                'rows' => collect(),
                'fetched_at' => now(),
            ];
        }

        $cacheKey = 'domains:live_dns:'.md5(implode(',', $zones));
        if ($bustCache) {
            Cache::forget($cacheKey);
        }

        $payload = Cache::remember(
            $cacheKey,
            $this->cacheTtlSeconds,
            function () use ($zones) {
                $out = [];
                foreach ($zones as $zone) {
                    $zone = $this->normalizeHost($zone);
                    if ($zone === null) {
                        continue;
                    }
                    $hosts = [$zone, 'www.'.$zone];
                    foreach ($hosts as $queryHost) {
                        $q = trim($queryHost, '.');
                        if ($q === '') {
                            continue;
                        }
                        $raw = @dns_get_record($q, DNS_ALL);
                        if (! is_array($raw)) {
                            continue;
                        }
                        foreach ($raw as $row) {
                            $n = $this->normalizeRow($row, $zone, $q);
                            if ($n !== null) {
                                $out[] = $n;
                            }
                        }
                    }
                }

                return collect($out)
                    ->unique(static fn (array $r) => ($r['zone'] ?? '').'|'.($r['queried'] ?? '').'|'.($r['type'] ?? '').'|'.($r['name'] ?? '').'|'.($r['value'] ?? ''))
                    ->sortBy(
                        static fn (array $r) => ($r['zone'] ?? '').'|'.($r['name'] ?? '').'|'.($r['type'] ?? '').'|'.($r['value'] ?? '')
                    )
                    ->values();
            }
        );

        return [
            'ok' => true,
            'error' => null,
            'rows' => $payload instanceof Collection ? $payload : collect($payload),
            'fetched_at' => now(),
        ];
    }

    private function normalizeHost(string $h): ?string
    {
        $h = strtolower(trim($h));
        $h = rtrim($h, '.');
        if ($h === '' || str_contains($h, '..') || str_contains($h, '/')) {
            return null;
        }

        return $h;
    }

    /**
     * @param  array<string, mixed>  $r
     * @return array{zone: string, name: string, type: string, value: string, priority: int|null, ttl: int|null, queried: string}|null
     */
    private function normalizeRow(array $r, string $zone, string $queried): ?array
    {
        $type = strtoupper((string) ($r['type'] ?? ''));
        $host = rtrim((string) ($r['host'] ?? ''), '.');
        $host = $host === '' ? $queried : $host;
        $ttl = isset($r['ttl']) ? (int) $r['ttl'] : null;
        if ($type === 'A' && ! empty($r['ip'])) {
            $value = (string) $r['ip'];
        } elseif ($type === 'AAAA' && ! empty($r['ipv6'])) {
            $value = (string) $r['ipv6'];
        } elseif ($type === 'CNAME' && ! empty($r['target'])) {
            $value = rtrim((string) $r['target'], '.');
        } elseif ($type === 'MX' && ! empty($r['target'])) {
            $value = rtrim((string) $r['target'], '.');
        } elseif ($type === 'NS' && ! empty($r['target'])) {
            $value = rtrim((string) $r['target'], '.');
        } elseif ($type === 'PTR' && ! empty($r['target'])) {
            $value = rtrim((string) $r['target'], '.');
        } elseif ($type === 'TXT') {
            $t = $r['txt'] ?? '';
            if (is_array($t)) {
                $t = implode('', $t);
            }
            $value = (string) $t;
        } elseif ($type === 'SOA') {
            $value = trim(
                (string) ($r['mname'] ?? '').
                ' '.
                (string) ($r['rname'] ?? '').
                ' '.
                (string) ($r['serial'] ?? '').
                ' '.
                (string) ($r['refresh'] ?? '').
                ' '.
                (string) ($r['retry'] ?? '').
                ' '.
                (string) ($r['expire'] ?? '').
                ' '.
                (string) ($r['minimum-ttl'] ?? $r['minimum_ttl'] ?? '')
            );
        } elseif ($type === 'SRV' && ! empty($r['target'])) {
            $p = (int) ($r['port'] ?? 0);
            $w = (int) ($r['weight'] ?? 0);
            $pr = (int) ($r['pri'] ?? 0);
            $value = rtrim((string) $r['target'], '.')." port {$p} weight {$w} (prio {$pr})";
        } else {
            if ($type === '' || in_array($type, ['A6', 'ANY'], true)) {
                return null;
            }

            $value = json_encode($r, JSON_UNESCAPED_SLASHES);
            if ($value === false) {
                $value = $type;
            }
        }

        $priority = null;
        if ($type === 'MX' && isset($r['pri'])) {
            $priority = (int) $r['pri'];
        }

        return [
            'zone' => $zone,
            'name' => $this->displayNameForZone($host, $zone),
            'type' => $type,
            'value' => $value,
            'priority' => $priority,
            'ttl' => $ttl,
            'queried' => $queried,
        ];
    }

    private function displayNameForZone(string $host, string $zone): string
    {
        $h = rtrim(strtolower($host), '.');
        $z = rtrim(strtolower($zone), '.');
        if ($h === $z) {
            return '@';
        }
        if (str_ends_with($h, '.'.$z)) {
            $sub = substr($h, 0, -strlen('.'.$z));

            return $sub !== '' ? $sub : '@';
        }

        return $host;
    }
}
