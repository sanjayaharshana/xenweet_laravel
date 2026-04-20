<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PublicIpResolver
{
    /**
     * Outbound public IPv4/IPv6 as seen by a simple HTTP lookup (cached briefly).
     */
    public function resolve(): ?string
    {
        $cached = Cache::get('app.outbound_public_ip');
        if (is_string($cached) && filter_var($cached, FILTER_VALIDATE_IP)) {
            return $cached;
        }

        try {
            $body = trim((string) Http::timeout(3)
                ->connectTimeout(2)
                ->get('https://api.ipify.org')
                ->body());

            if (filter_var($body, FILTER_VALIDATE_IP)) {
                Cache::put('app.outbound_public_ip', $body, now()->addMinutes(10));

                return $body;
            }
        } catch (\Throwable) {
            // offline / firewall — caller may fall back to local hints
        }

        return null;
    }
}
