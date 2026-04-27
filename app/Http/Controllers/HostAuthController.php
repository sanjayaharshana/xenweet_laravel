<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureHostPanelAccess;
use App\Models\Hosting;
use App\Services\HostPanelTwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class HostAuthController extends Controller
{
    public function showLogin(Hosting $hosting): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('hosts.panel', $hosting);
        }

        return view('auth.host-login', [
            'hosting' => $hosting,
        ]);
    }

    public function login(Request $request, Hosting $hosting, HostPanelTwoFactorService $twoFactor): RedirectResponse
    {
        $validated = $request->validate([
            'panel_username' => ['required', 'string', 'max:100'],
            'panel_password' => ['required', 'string', 'max:255'],
        ]);

        if (! $this->credentialsMatch($hosting, $validated['panel_username'], $validated['panel_password'])) {
            return back()
                ->withErrors(['panel_username' => 'Invalid host panel credentials.'])
                ->onlyInput('panel_username');
        }

        if ($twoFactor->isEnabled($hosting)) {
            $twoFactor->startLoginChallenge($request, $hosting);

            return redirect()->route('hosts.auth.2fa.challenge', $hosting);
        }

        return $this->grantHostPanelAccess($request, $hosting);
    }

    public function logout(Request $request, Hosting $hosting): RedirectResponse
    {
        $hostId = (int) $hosting->getKey();
        $allowedHostIds = array_map('intval', (array) $request->session()->get(EnsureHostPanelAccess::SESSION_KEY, []));
        $filtered = array_values(array_filter(
            $allowedHostIds,
            fn (int $id): bool => $id !== $hostId
        ));
        $request->session()->put(EnsureHostPanelAccess::SESSION_KEY, $filtered);

        $verified2faIds = array_map('intval', (array) $request->session()->get(EnsureHostPanelAccess::SESSION_2FA_KEY, []));
        $verifiedFiltered = array_values(array_filter(
            $verified2faIds,
            fn (int $id): bool => $id !== $hostId
        ));
        $request->session()->put(EnsureHostPanelAccess::SESSION_2FA_KEY, $verifiedFiltered);

        $request->session()->regenerateToken();

        return redirect()
            ->route('hosts.auth.login', $hosting)
            ->with('status', 'Signed out from host panel.');
    }

    public function showTwoFactorChallenge(Request $request, Hosting $hosting, HostPanelTwoFactorService $twoFactor): View|RedirectResponse
    {
        if (! $twoFactor->hasPendingLoginChallenge($request, $hosting)) {
            return redirect()
                ->route('hosts.auth.login', $hosting)
                ->with('status', 'Please sign in again.');
        }

        return view('auth.host-login-2fa', [
            'hosting' => $hosting,
        ]);
    }

    public function verifyTwoFactorChallenge(Request $request, Hosting $hosting, HostPanelTwoFactorService $twoFactor): RedirectResponse
    {
        if (! $twoFactor->hasPendingLoginChallenge($request, $hosting)) {
            return redirect()
                ->route('hosts.auth.login', $hosting)
                ->with('status', 'Please sign in again.');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        if (! $twoFactor->verifyOtpOrRecoveryCode($hosting, (string) $validated['code'])) {
            return back()->withErrors(['code' => 'Invalid authentication code.']);
        }

        $twoFactor->clearLoginChallenge($request);

        return $this->grantHostPanelAccess($request, $hosting);
    }

    private function credentialsMatch(Hosting $hosting, string $username, string $password): bool
    {
        if (! hash_equals((string) $hosting->panel_username, $username)) {
            return false;
        }

        $storedPassword = (string) $hosting->panel_password;
        if ($storedPassword === '') {
            return false;
        }

        $hashInfo = Hash::info($storedPassword);
        if (! empty($hashInfo['algo'])) {
            return Hash::check($password, $storedPassword);
        }

        return hash_equals($storedPassword, $password);
    }

    private function grantHostPanelAccess(Request $request, Hosting $hosting): RedirectResponse
    {
        $request->session()->regenerate();
        $hostId = (int) $hosting->getKey();

        $allowedHostIds = array_map('intval', (array) $request->session()->get(EnsureHostPanelAccess::SESSION_KEY, []));
        $allowedHostIds[] = $hostId;
        $allowedHostIds = array_values(array_unique($allowedHostIds));
        $request->session()->put(EnsureHostPanelAccess::SESSION_KEY, $allowedHostIds);

        $verified2faIds = array_map('intval', (array) $request->session()->get(EnsureHostPanelAccess::SESSION_2FA_KEY, []));
        $verified2faIds[] = $hostId;
        $verified2faIds = array_values(array_unique($verified2faIds));
        $request->session()->put(EnsureHostPanelAccess::SESSION_2FA_KEY, $verified2faIds);

        return redirect()->intended(route('hosts.panel', $hosting));
    }
}
