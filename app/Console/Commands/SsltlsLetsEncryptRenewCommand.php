<?php

namespace App\Console\Commands;

use App\Models\Hosting;
use Illuminate\Console\Command;
use Modules\SslTls\Services\SslTlsLetsEncryptService;
use Throwable;

class SsltlsLetsEncryptRenewCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'ssltls:letsencrypt-renew';

    /**
     * @var string
     */
    protected $description = "Run `certbot renew`, then re-copy certificates and reload nginx for sites issued via the panel (Let's Encrypt Auto SSL).";

    public function handle(SslTlsLetsEncryptService $letsEncrypt): int
    {
        if (! (bool) config('ssltls.letsencrypt_enabled', false)) {
            $this->warn("Let's Encrypt Auto SSL is disabled (SSLTLS_LETSENCRYPT_ENABLED). Nothing to do.");

            return self::SUCCESS;
        }

        try {
            $letsEncrypt->runCertbotRenewProcess();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $n = 0;
        $hostings = Hosting::query()
            ->whereHas('sslStore', function ($q): void {
                $q->whereNotNull('letsencrypt_issued_at');
            })
            ->get();

        foreach ($hostings as $hosting) {
            try {
                $line = $letsEncrypt->resyncFromLiveDirectory($hosting);
                $this->line($line);
                if (! str_starts_with($line, 'Skipped:')) {
                    $n++;
                }
            } catch (Throwable $e) {
                $this->warn($hosting->siteHost().': '.$e->getMessage());
            }
        }

        $this->info("Resynced {$n} hosting account(s) from Let's Encrypt live directories.");

        return self::SUCCESS;
    }
}
