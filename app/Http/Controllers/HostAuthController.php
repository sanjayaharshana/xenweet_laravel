<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureHostPanelAccess;
use App\Models\Hosting;
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

    public function login(Request $request, Hosting $hosting): RedirectResponse
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

        $request->session()->regenerate();

        $allowedHostIds = array_map('intval', (array) $request->session()->get(EnsureHostPanelAccess::SESSION_KEY, []));
        $allowedHostIds[] = (int) $hosting->getKey();
        $allowedHostIds = array_values(array_unique($allowedHostIds));
        $request->session()->put(EnsureHostPanelAccess::SESSION_KEY, $allowedHostIds);

        return redirect()->intended(route('hosts.panel', $hosting));
    }

    public function logout(Request $request, Hosting $hosting): RedirectResponse
    {
        $allowedHostIds = array_map('intval', (array) $request->session()->get(EnsureHostPanelAccess::SESSION_KEY, []));
        $filtered = array_values(array_filter(
            $allowedHostIds,
            fn (int $id): bool => $id !== (int) $hosting->getKey()
        ));
        $request->session()->put(EnsureHostPanelAccess::SESSION_KEY, $filtered);
        $request->session()->regenerateToken();

        return redirect()
            ->route('hosts.auth.login', $hosting)
            ->with('status', 'Signed out from host panel.');
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
}
