<?php

declare(strict_types=1);

namespace App\Support\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Post-login redirect limited to the signed-in guard's own area.
 *
 * Laravel keeps one "intended URL" per session for every guard. A guest who opens /superadmin and
 * then signs in on the admin form must not be sent to /superadmin (and back to its login page),
 * so each login form only honors an intended URL inside its own path prefix.
 */
final class AreaIntendedRedirect
{
    /**
     * Redirect to the intended URL when it lies under the area prefix, otherwise to the fallback.
     *
     * @param Request $request Current request holding the session.
     * @param string $areaPath Area path prefix such as "admin" or "superadmin".
     * @param string $fallback URL used when there is no usable intended URL.
     * @return RedirectResponse Redirect response.
     */
    public static function to(Request $request, string $areaPath, string $fallback): RedirectResponse
    {
        $intended = (string) $request->session()->pull('url.intended', '');
        $path = trim((string) parse_url($intended, PHP_URL_PATH), '/');
        $sameHost = parse_url($intended, PHP_URL_HOST) === $request->getHost();
        $inArea = $path === $areaPath || str_starts_with($path, $areaPath.'/');

        return redirect()->to($intended !== '' && $sameHost && $inArea ? $intended : $fallback);
    }
}
