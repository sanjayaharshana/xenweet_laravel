<?php

namespace Modules\SslTls\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

class SslTlsOpenSslService
{
    public const KEY_RSA2048 = 'rsa2048';

    public const KEY_EC256 = 'ec256';

    public function generatePrivateKey(string $keyType): string
    {
        $this->assertKeyType($keyType);
        $pem = $this->runScript(['genkey', $keyType], 'private key');
        $this->assertPemContains($pem, 'PRIVATE KEY');

        return $pem;
    }

    /**
     * @param  array{CN: string, C?: string, ST?: string, L?: string, O?: string, OU?: string, emailAddress?: string}  $dn
     */
    public function generateCsr(string $privateKeyPem, array $dn): string
    {
        $pem = trim($privateKeyPem);
        if ($pem === '' || ! str_contains($pem, 'BEGIN') || ! str_contains($pem, 'PRIVATE KEY')) {
            throw new RuntimeException('Private key must be a valid PEM (including BEGIN/END lines).');
        }

        $subject = $this->buildSubject($dn);
        $tmp = tempnam(sys_get_temp_dir(), 'ssltls_k_');
        if ($tmp === false) {
            throw new RuntimeException('Could not create a temporary file for the private key.');
        }

        try {
            chmod($tmp, 0600);
            if (file_put_contents($tmp, $pem."\n") === false) {
                throw new RuntimeException('Could not write the private key to a temporary file.');
            }

            $out = $this->runScript(['gencsr', $tmp, $subject], 'CSR');
            $this->assertPemContains($out, 'CERTIFICATE REQUEST');

            return $out;
        } finally {
            @unlink($tmp);
        }
    }

    /**
     * @param  array{CN: string, C?: string, ST?: string, L?: string, O?: string, OU?: string, emailAddress?: string}  $dn
     */
    public function buildSubject(array $dn): string
    {
        $cn = trim((string) ($dn['CN'] ?? ''));
        if ($cn === '') {
            throw new RuntimeException('Common name (CN) is required.');
        }

        $segments = [];
        if (! empty($dn['C'])) {
            $segments[] = 'C='.$this->escapeDn((string) $dn['C']);
        }
        if (! empty($dn['ST'])) {
            $segments[] = 'ST='.$this->escapeDn((string) $dn['ST']);
        }
        if (! empty($dn['L'])) {
            $segments[] = 'L='.$this->escapeDn((string) $dn['L']);
        }
        if (! empty($dn['O'])) {
            $segments[] = 'O='.$this->escapeDn((string) $dn['O']);
        }
        if (! empty($dn['OU'])) {
            $segments[] = 'OU='.$this->escapeDn((string) $dn['OU']);
        }
        $segments[] = 'CN='.$this->escapeDn($cn);
        if (! empty($dn['emailAddress'])) {
            $segments[] = 'emailAddress='.$this->escapeDn((string) $dn['emailAddress']);
        }

        return '/'.implode('/', $segments);
    }

    private function escapeDn(string $value): string
    {
        return str_replace(['\\', '/'], ['\\\\', '\\/'], $value);
    }

    private function assertKeyType(string $keyType): void
    {
        if (! in_array($keyType, [self::KEY_RSA2048, self::KEY_EC256], true)) {
            throw new RuntimeException('Invalid key type.');
        }
    }

    private function assertPemContains(string $pem, string $needle): void
    {
        if (! str_contains($pem, $needle)) {
            throw new RuntimeException('OpenSSL did not return valid PEM output.');
        }
    }

    /**
     * @param  list<string>  $args  script args after script path (e.g. genkey rsa2048)
     */
    private function runScript(array $args, string $label): string
    {
        $script = (string) config('ssltls.script_path', '');
        if ($script === '' || ! is_readable($script)) {
            throw new RuntimeException(
                'SSL/TLS helper script is not configured or not readable. Set ssltls.script_path in config.'
            );
        }

        $openssl = (string) config('ssltls.openssl_binary', 'openssl');
        $command = array_merge(['sh', $script], $args);

        $process = new Process(
            $command,
            null,
            array_merge($this->safeEnv(), ['OPENSSL' => $openssl]),
            null,
            90.0
        );
        $process->run();

        $stdout = $process->getOutput();
        $stderr = trim($process->getErrorOutput());

        if (! $process->isSuccessful()) {
            $hint = $stderr !== '' ? $this->sanitizeProcessError($stderr) : 'exit code '.$process->getExitCode();
            throw new RuntimeException('OpenSSL failed to generate '.$label.': '.$hint);
        }

        $out = trim($stdout);
        if ($out === '') {
            throw new RuntimeException('OpenSSL returned empty output for '.$label.'.');
        }

        return $out;
    }

    /**
     * Avoid passing the entire parent environment on Windows; keep minimal env for openssl + sh.
     *
     * @return array<string, string>
     */
    private function safeEnv(): array
    {
        $path = getenv('PATH');
        if ($path === false || $path === '') {
            $path = '/usr/bin:/bin:/usr/local/bin';
        }
        $home = getenv('HOME');
        if ($home === false || $home === '') {
            $home = '/tmp';
        }
        $lang = getenv('LANG');
        if ($lang === false || $lang === '') {
            $lang = 'C.UTF-8';
        }

        return [
            'PATH' => $path,
            'HOME' => $home,
            'LANG' => $lang,
        ];
    }

    private function sanitizeProcessError(string $stderr): string
    {
        $s = preg_replace('#/var/folders/[^\s]+#', '[tmp]', $stderr) ?? $stderr;
        $s = preg_replace('#/tmp/[^\s]+#', '[tmp]', $s) ?? $s;

        return trim((string) $s);
    }
}
