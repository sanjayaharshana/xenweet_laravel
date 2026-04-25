<?php

namespace Modules\SslTls\Services;

use App\Models\Hosting;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;

class SslTlsNginxPemService
{
    /**
     * @return list<string>
     */
    public function extractCertificatePemBlocks(string $pem): array
    {
        $input = trim($pem);
        if ($input === '') {
            return [];
        }

        if (! str_contains($input, "\n") && str_contains($input, '\n')) {
            $input = str_replace('\n', "\n", $input);
        }

        $matches = [];
        preg_match_all(
            '/-----BEGIN (?:TRUSTED )?CERTIFICATE-----\s*[\s\S]+?\s*-----END (?:TRUSTED )?CERTIFICATE-----/m',
            $input,
            $matches
        );
        $blocks = [];
        foreach (($matches[0] ?? []) as $raw) {
            $b = trim((string) $raw);
            if ($b !== '') {
                $b = str_replace('BEGIN TRUSTED CERTIFICATE', 'BEGIN CERTIFICATE', $b);
                $b = str_replace('END TRUSTED CERTIFICATE', 'END CERTIFICATE', $b);
                $blocks[] = $b;
            }
        }

        return $blocks;
    }

    /**
     * @param  list<string>  $blocks
     */
    public function areValidX509PemBlocks(array $blocks): bool
    {
        foreach ($blocks as $block) {
            $x = @openssl_x509_read($block);
            if ($x === false) {
                return false;
            }
            openssl_x509_free($x);
        }

        return true;
    }

    /**
     * @return array{0: string, 1: list<string>} leaf, remaining chain blocks
     */
    public function splitFullchainPemToLeafAndChain(string $fullchainPem): array
    {
        $blocks = $this->extractCertificatePemBlocks($fullchainPem);
        if ($blocks === []) {
            throw new RuntimeException('Full chain PEM is empty or not valid certificate PEM.');
        }
        if (! $this->areValidX509PemBlocks($blocks)) {
            throw new RuntimeException('Full chain does not contain valid X.509 certificate PEM data.');
        }
        $leaf = array_shift($blocks);

        return [$leaf, $blocks];
    }

    /**
     * Writes key + cert material under the host SSL directory and (if configured) updates nginx and reloads.
     *
     * @param  list<string>  $leafPemBlocks
     * @param  list<string>  $chainPemBlocks
     */
    public function materializePemToDiskAndReloadNginx(
        Hosting $hosting,
        string $privateKeyPem,
        array $leafPemBlocks,
        array $chainPemBlocks
    ): string {
        if ($privateKeyPem === '' || ! str_contains($privateKeyPem, 'PRIVATE KEY')) {
            throw new RuntimeException('Private key is missing or invalid.');
        }
        if ($leafPemBlocks === [] || ! $this->areValidX509PemBlocks($leafPemBlocks)) {
            throw new RuntimeException('Certificate is not valid X.509 PEM data.');
        }
        if ($chainPemBlocks !== [] && ! $this->areValidX509PemBlocks($chainPemBlocks)) {
            throw new RuntimeException('Certificate chain contains invalid X.509 certificate data.');
        }

        $sslDir = $this->resolveSslInstallDirectory($hosting);
        $base = preg_replace('/[^a-zA-Z0-9.\-_]/', '-', $hosting->siteHost()) ?: 'host-'.$hosting->id;

        $keyPath = $sslDir.DIRECTORY_SEPARATOR.$base.'.key.pem';
        $certPath = $sslDir.DIRECTORY_SEPARATOR.$base.'.cert.pem';
        $chainPath = $sslDir.DIRECTORY_SEPARATOR.$base.'.chain.pem';
        $fullchainPath = $sslDir.DIRECTORY_SEPARATOR.$base.'.fullchain.pem';

        File::put($keyPath, $privateKeyPem."\n");
        @chmod($keyPath, 0600);

        File::put($certPath, implode("\n", $leafPemBlocks)."\n");
        @chmod($certPath, 0644);

        $fullchain = implode("\n", $leafPemBlocks)."\n";
        if ($chainPemBlocks !== []) {
            File::put($chainPath, implode("\n", $chainPemBlocks)."\n");
            @chmod($chainPath, 0644);
            $fullchain .= implode("\n", $chainPemBlocks)."\n";
        } elseif (File::exists($chainPath)) {
            File::delete($chainPath);
        }

        File::put($fullchainPath, $fullchain);
        @chmod($fullchainPath, 0644);

        return $this->runNginxSslInstall($hosting, $sslDir, $keyPath, $fullchainPath);
    }

    /**
     * When the host has key + fullchain on disk (Auto SSL or manual install), re-run the SSL-aware
     * Nginx vhost (HTTP + ACME on :80, HTTPS on :443) with the current PHP-FPM socket.
     * Call this from PHP version changes: the HTTP-only vhost script would otherwise overwrite the
     * :443 block and break HTTPS/PHP.
     */
    public function reapplyNginxWhenMaterializedSslOnDisk(Hosting $hosting): ?string
    {
        $paths = $this->materializedSslPemFilePathsIfPresent($hosting);
        if ($paths === null) {
            return null;
        }
        [$keyPath, $fullchainPath, $sslDir] = $paths;

        return $this->runNginxSslInstall($hosting, $sslDir, $keyPath, $fullchainPath);
    }

    /**
     * @return array{0: string, 1: string, 2: string}|null key, fullchain, sslDir
     */
    private function materializedSslPemFilePathsIfPresent(Hosting $hosting): ?array
    {
        $root = trim((string) ($hosting->host_root_path ?? ''));
        if ($root === '') {
            return null;
        }
        $base = preg_replace('/[^a-zA-Z0-9.\-_]/', '-', $hosting->siteHost()) ?: 'host-'.$hosting->id;
        $sslDir = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'ssl';
        $keyPath = $sslDir.DIRECTORY_SEPARATOR.$base.'.key.pem';
        $fullchainPath = $sslDir.DIRECTORY_SEPARATOR.$base.'.fullchain.pem';
        if (! is_file($keyPath) || ! is_file($fullchainPath)) {
            return null;
        }
        if (! is_readable($keyPath) || ! is_readable($fullchainPath)) {
            return null;
        }

        return [$keyPath, $fullchainPath, $sslDir];
    }

    private function resolveSslInstallDirectory(Hosting $hosting): string
    {
        $root = trim((string) ($hosting->host_root_path ?? ''));
        if ($root === '') {
            throw new RuntimeException('Host root path is not set for this hosting account.');
        }

        $sslDir = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'ssl';
        File::ensureDirectoryExists($sslDir);
        if (! is_writable($sslDir)) {
            throw new RuntimeException('SSL directory is not writable: '.$sslDir);
        }

        return $sslDir;
    }

    private function runNginxSslInstall(
        Hosting $hosting,
        string $sslDir,
        string $keyPath,
        string $fullchainPath
    ): string {
        $systemBin = (string) config('ssltls.nginx_ssl_system_install_bin', '');
        $script = (string) config('ssltls.nginx_ssl_install_script', '');
        $timeout = (float) config('ssltls.nginx_ssl_install_timeout', 90);

        $phpSock = $hosting->webPhpFpmSocketPath();

        $command = null;
        if ($systemBin !== '' && is_executable($systemBin)) {
            // Pass socket as 5th argv: `sudo` resets the environment, so PHP_FPM_SOCKET would be lost to the root helper.
            $command = ['sudo', '-n', $systemBin, $hosting->siteHost(), (string) $hosting->web_root_path, $keyPath, $fullchainPath, $phpSock];
        } elseif ($script !== '' && is_file($script)) {
            $command = ['bash', $script, $hosting->siteHost(), (string) $hosting->web_root_path, $keyPath, $fullchainPath, $phpSock];
        }

        if ($command === null) {
            throw new RuntimeException(
                'Nginx SSL installer is not configured. Set SSLTLS_NGINX_SSL_INSTALL_SCRIPT or SSLTLS_NGINX_SSL_SYSTEM_INSTALL_BIN.'
            );
        }

        $process = new Process($command, base_path(), [
            'SSL_DIR' => $sslDir,
            'PHP_FPM_SOCKET' => $phpSock,
        ], null, $timeout);
        $process->run();
        $out = trim($process->getOutput()."\n".$process->getErrorOutput());

        if (! $process->isSuccessful()) {
            if (str_contains(strtolower($out), 'a password is required')) {
                throw new RuntimeException(
                    'Nginx SSL activate failed: sudo requires password. Run once on server: bash scripts/install-xenweet-nginx-sudo.sh www-data'
                );
            }
            throw new RuntimeException('Nginx SSL activate failed: '.($out !== '' ? $out : 'no output'));
        }

        return $out !== '' ? $out : 'Nginx SSL vhost reloaded.';
    }
}
