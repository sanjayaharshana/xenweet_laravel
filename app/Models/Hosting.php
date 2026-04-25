<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Hosting extends Model
{
    protected $fillable = [
        'domain',
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
            'php_extensions' => 'array',
            'php_ini_options' => 'array',
            'provisioned_at' => 'datetime',
        ];
    }

    public function sslStore(): HasOne
    {
        return $this->hasOne(HostingSslStore::class);
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

    /**
     * Unix socket for PHP-FPM that Nginx should use for this account’s website only.
     * Does not change the system/CLI `php` binary used in SSH or cron.
     */
    public function webPhpFpmSocketPath(): string
    {
        $override = config('hosting_provision.php_fpm_socket');
        if (is_string($override) && $override !== '') {
            return $override;
        }

        $v = trim((string) $this->php_version);
        if (preg_match('/^(\d+)\.(\d+)/', $v, $m)) {
            return '/var/run/php/php'.$m[1].'.'.$m[2].'-fpm.sock';
        }

        return '/var/run/php/php8.3-fpm.sock';
    }
}
