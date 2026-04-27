<?php

namespace App\Services;

use App\Models\Hosting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HostPanelTwoFactorService
{
    public const LOGIN_PENDING_HOST_ID = 'host_2fa_login_pending_host_id';
    public const SETUP_SECRET_SESSION_KEY = 'host_2fa_setup_secret:';

    public function isEnabled(Hosting $hosting): bool
    {
        return (bool) ($hosting->panel_2fa_enabled ?? false)
            && filled((string) ($hosting->panel_2fa_secret ?? ''));
    }

    public function ensureSetupSecret(Request $request, Hosting $hosting): string
    {
        $key = self::SETUP_SECRET_SESSION_KEY.(int) $hosting->getKey();
        $existing = (string) $request->session()->get($key, '');
        if ($existing !== '') {
            return $existing;
        }

        $secret = $this->generateSecret();
        $request->session()->put($key, $secret);

        return $secret;
    }

    public function clearSetupSecret(Request $request, Hosting $hosting): void
    {
        $request->session()->forget(self::SETUP_SECRET_SESSION_KEY.(int) $hosting->getKey());
    }

    public function startLoginChallenge(Request $request, Hosting $hosting): void
    {
        $request->session()->put(self::LOGIN_PENDING_HOST_ID, (int) $hosting->getKey());
    }

    public function hasPendingLoginChallenge(Request $request, Hosting $hosting): bool
    {
        return (int) $request->session()->get(self::LOGIN_PENDING_HOST_ID, 0) === (int) $hosting->getKey();
    }

    public function clearLoginChallenge(Request $request): void
    {
        $request->session()->forget(self::LOGIN_PENDING_HOST_ID);
    }

    /**
     * @return array<int, string> plain recovery codes (show once)
     */
    public function enable(Hosting $hosting, string $secret): array
    {
        $codes = $this->generateRecoveryCodes();
        $hosting->forceFill([
            'panel_2fa_enabled' => true,
            'panel_2fa_secret' => $secret,
            'panel_2fa_recovery_codes' => $this->hashRecoveryCodes($codes),
        ])->save();

        return $codes;
    }

    /**
     * @return array<int, string> plain recovery codes (show once)
     */
    public function regenerateRecoveryCodes(Hosting $hosting): array
    {
        $codes = $this->generateRecoveryCodes();
        $hosting->forceFill([
            'panel_2fa_recovery_codes' => $this->hashRecoveryCodes($codes),
        ])->save();

        return $codes;
    }

    public function disable(Hosting $hosting): void
    {
        $hosting->forceFill([
            'panel_2fa_enabled' => false,
            'panel_2fa_secret' => null,
            'panel_2fa_recovery_codes' => null,
        ])->save();
    }

    public function verifyOtpOrRecoveryCode(Hosting $hosting, string $input): bool
    {
        $code = preg_replace('/\s+/', '', trim($input));
        if ($code === '' || ! $this->isEnabled($hosting)) {
            return false;
        }

        if ($this->verifyTotpCode((string) $hosting->panel_2fa_secret, $code)) {
            return true;
        }

        $hashes = $this->recoveryCodeHashes($hosting);
        if ($hashes === []) {
            return false;
        }

        $normalized = Str::lower(str_replace('-', '', $code));
        foreach ($hashes as $idx => $hash) {
            if (! is_string($hash)) {
                continue;
            }
            if (hash_equals($hash, hash('sha256', $normalized))) {
                unset($hashes[$idx]); // one-time recovery code
                $hosting->forceFill(['panel_2fa_recovery_codes' => array_values($hashes)])->save();

                return true;
            }
        }

        return false;
    }

    public function provisioningUri(Hosting $hosting, string $secret): string
    {
        $issuer = (string) config('app.name', 'Xenweet');
        $label = $issuer.':'.$hosting->siteHost().'('.$hosting->panel_username.')';

        return 'otpauth://totp/'.rawurlencode($label)
            .'?secret='.rawurlencode($secret)
            .'&issuer='.rawurlencode($issuer)
            .'&algorithm=SHA1&digits=6&period=30';
    }

    public function verifyTotpCode(string $base32Secret, string $code): bool
    {
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }
        $secret = $this->base32Decode($base32Secret);
        if ($secret === '') {
            return false;
        }

        $timeSlice = (int) floor(time() / 30);
        for ($offset = -1; $offset <= 1; $offset++) {
            $candidate = $this->totpAt($secret, $timeSlice + $offset);
            if (hash_equals($candidate, $code)) {
                return true;
            }
        }

        return false;
    }

    private function totpAt(string $secret, int $timeSlice): string
    {
        $time = pack('N*', 0).pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $time, $secret, true);
        $offset = ord(substr($hash, -1)) & 0x0f;
        $chunk = substr($hash, $offset, 4);
        $value = unpack('N', $chunk)[1] & 0x7fffffff;
        $otp = $value % 1000000;

        return str_pad((string) $otp, 6, '0', STR_PAD_LEFT);
    }

    private function generateSecret(int $length = 32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $secret;
    }

    /**
     * @return array<int, string>
     */
    private function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $raw = Str::lower(Str::random(10));
            $codes[] = substr($raw, 0, 5).'-'.substr($raw, 5, 5);
        }

        return $codes;
    }

    /**
     * @param array<int, string> $codes
     * @return array<int, string>
     */
    private function hashRecoveryCodes(array $codes): array
    {
        return array_map(
            static fn (string $code): string => hash('sha256', Str::lower(str_replace('-', '', $code))),
            $codes
        );
    }

    /**
     * @return array<int, string>
     */
    private function recoveryCodeHashes(Hosting $hosting): array
    {
        $raw = $hosting->panel_2fa_recovery_codes;
        if (is_array($raw)) {
            return array_values(array_filter($raw, static fn ($v): bool => is_string($v) && $v !== ''));
        }

        return [];
    }

    private function base32Decode(string $secret): string
    {
        $secret = strtoupper(trim($secret));
        $secret = preg_replace('/[^A-Z2-7]/', '', $secret) ?? '';
        if ($secret === '') {
            return '';
        }

        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split($secret) as $char) {
            $val = strpos($alphabet, $char);
            if ($val === false) {
                return '';
            }
            $bits .= str_pad(decbin($val), 5, '0', STR_PAD_LEFT);
        }

        $out = '';
        for ($i = 0; $i + 8 <= strlen($bits); $i += 8) {
            $out .= chr(bindec(substr($bits, $i, 8)));
        }

        return $out;
    }
}
