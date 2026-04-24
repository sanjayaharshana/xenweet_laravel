<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hosting extends Model
{
    protected $fillable = [
        'domain',
        'ssl_san_hostnames',
        'server_ip',
        'host_root_path',
        'web_root_path',
        'plan',
        'panel_username',
        'panel_password',
        'status',
        'provision_status',
        'provision_log',
        'provisioned_at',
        'php_version',
        'disk_usage_mb',
    ];

    protected function casts(): array
    {
        return [
            'panel_password' => 'encrypted',
            'provisioned_at' => 'datetime',
            'ssl_san_hostnames' => 'array',
        ];
    }

    /**
     * Strip protocol/path so DNS and URLs use a bare hostname (example.com).
     */
    public static function normalizeDomainName(string $domain): string
    {
        $d = trim($domain);
        $d = preg_replace('#^https?://#i', '', $d);
        $d = trim($d, '/');
        if ($d === '') {
            return $d;
        }

        return explode('/', $d, 2)[0];
    }

    public function siteHost(): string
    {
        return self::normalizeDomainName((string) $this->domain);
    }

    /**
     * URL for the customer's public site (DNS should point to server_ip).
     */
    public function publicSiteUrl(): string
    {
        $scheme = (string) config('hosting.open_host_scheme', 'http');

        return $scheme.'://'.$this->siteHost();
    }
}
