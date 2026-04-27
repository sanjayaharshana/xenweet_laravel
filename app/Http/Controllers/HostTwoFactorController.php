<?php

namespace App\Http\Controllers;

use App\Models\Hosting;
use App\Services\HostPanelTwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HostTwoFactorController extends Controller
{
    public function edit(Request $request, Hosting $hosting, HostPanelTwoFactorService $twoFactor): View
    {
        $enabled = $twoFactor->isEnabled($hosting);
        $setupSecret = null;
        $setupUri = null;
        if (! $enabled) {
            $setupSecret = $twoFactor->ensureSetupSecret($request, $hosting);
            $setupUri = $twoFactor->provisioningUri($hosting, $setupSecret);
        }

        return view('panel.host-two-factor', [
            'hosting' => $hosting,
            'enabled' => $enabled,
            'setupSecret' => $setupSecret,
            'setupUri' => $setupUri,
            'recoveryCodes' => (array) $request->session()->get('host_2fa_recovery_codes', []),
        ]);
    }

    public function update(Request $request, Hosting $hosting, HostPanelTwoFactorService $twoFactor): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:enable,disable,regenerate_recovery'],
            'code' => ['nullable', 'string', 'max:32'],
        ]);

        $action = (string) $validated['action'];
        if ($action === 'enable') {
            $secret = $twoFactor->ensureSetupSecret($request, $hosting);
            $code = (string) ($validated['code'] ?? '');
            if (! $twoFactor->verifyTotpCode($secret, preg_replace('/\s+/', '', $code))) {
                return redirect()
                    ->route('hosts.security.2fa', $hosting)
                    ->withErrors(['code' => 'Invalid authenticator code.'])
                    ->withInput();
            }
            $recoveryCodes = $twoFactor->enable($hosting, $secret);
            $twoFactor->clearSetupSecret($request, $hosting);

            return redirect()
                ->route('hosts.security.2fa', $hosting)
                ->with('success', 'Two-factor authentication enabled for this host panel login.')
                ->with('host_2fa_recovery_codes', $recoveryCodes);
        }

        if ($action === 'disable') {
            $twoFactor->disable($hosting);
            $twoFactor->clearSetupSecret($request, $hosting);

            return redirect()
                ->route('hosts.security.2fa', $hosting)
                ->with('success', 'Two-factor authentication disabled.');
        }

        if (! $twoFactor->isEnabled($hosting)) {
            return redirect()
                ->route('hosts.security.2fa', $hosting)
                ->with('error', 'Enable 2FA first before regenerating recovery codes.');
        }

        $recoveryCodes = $twoFactor->regenerateRecoveryCodes($hosting);

        return redirect()
            ->route('hosts.security.2fa', $hosting)
            ->with('success', 'Recovery codes regenerated.')
            ->with('host_2fa_recovery_codes', $recoveryCodes);
    }
}
