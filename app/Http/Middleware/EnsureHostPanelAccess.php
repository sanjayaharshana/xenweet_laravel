<?php

namespace App\Http\Middleware;

use App\Models\Hosting;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureHostPanelAccess
{
    public const SESSION_KEY = 'host_panel_auth_ids';
    public const SESSION_2FA_KEY = 'host_panel_2fa_verified_ids';

    /**
     * Allow admin users or host-credential session for the matched hosting.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            return $next($request);
        }

        $hosting = $this->resolveHosting($request);
        if (! $hosting) {
            abort(403, 'Hosting context is required.');
        }

        $allowedHostIds = array_map('intval', (array) $request->session()->get(self::SESSION_KEY, []));

        $hostId = (int) $hosting->getKey();
        if (in_array($hostId, $allowedHostIds, true)) {
            $requiresTwoFactor = (bool) ($hosting->panel_2fa_enabled ?? false)
                && filled((string) ($hosting->panel_2fa_secret ?? ''));
            if (! $requiresTwoFactor) {
                return $next($request);
            }

            $verified2faIds = array_map('intval', (array) $request->session()->get(self::SESSION_2FA_KEY, []));
            if (in_array($hostId, $verified2faIds, true)) {
                return $next($request);
            }
        }

        return redirect()
            ->guest(route('hosts.auth.login', $hosting))
            ->with('host_auth_notice', 'Please sign in with host panel credentials.');
    }

    private function resolveHosting(Request $request): ?Hosting
    {
        $routeValue = $request->route('hosting');
        if ($routeValue instanceof Hosting) {
            return $routeValue;
        }

        if (is_numeric($routeValue)) {
            return Hosting::query()->find((int) $routeValue);
        }

        return null;
    }
}
