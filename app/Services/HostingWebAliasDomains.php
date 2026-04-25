<?php

namespace App\Services;

use App\Models\Hosting;
use Illuminate\Support\Facades\Schema;

/**
 * Extra hostnames (aliases) for Nginx server_name from the Domains module.
 */
final class HostingWebAliasDomains
{
    /**
     * Space-separated extra server_name values for NGINX_EXTRA_SERVER_NAMES (excludes primary site host).
     */
    public static function nginxExtraServerNamesString(Hosting $hosting): string
    {
        if (! class_exists(\Modules\Domains\Models\HostDomain::class) || ! Schema::hasTable('host_domains')) {
            return '';
        }

        $primary = mb_strtolower($hosting->siteHost());
        $names = \Modules\Domains\Models\HostDomain::query()
            ->where('hosting_id', $hosting->id)
            ->orderBy('id')
            ->pluck('domain');

        $out = [];
        foreach ($names as $d) {
            $d = trim((string) $d);
            if ($d === '' || mb_strtolower($d) === $primary) {
                continue;
            }
            $out[] = $d;
        }

        $out = array_values(array_unique($out));

        return trim(implode(' ', $out));
    }
}
