<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\AdminAccount;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAdmin
{
    /**
     * Reject disabled admin sessions on routes requiring admin authentication.
     *
     * @param Request $request Incoming request with its resolved route.
     * @param Closure(Request): Response $next Next middleware handler.
     * @return Response Redirect to login or the downstream response.
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException For denied JSON requests.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user('admin');

        if ($request->is('admin', 'admin/*')
            && in_array('auth:admin', $request->route()?->gatherMiddleware() ?? [], true)
            && $admin && ! $admin->isActive()) {
            Auth::guard('admin')->logout();

            if ($request->expectsJson()) {
                abort(Response::HTTP_FORBIDDEN);
            }

            return redirect()->route('admin.login.page');
        }

        if ($admin && $this->isAdminRoute($request) && ! $this->passwordFingerprintMatches($request, $admin)) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                abort(Response::HTTP_UNAUTHORIZED);
            }

            return redirect()->route('admin.login.page');
        }

        return $next($request);
    }

    /**
     * Build the value stored in the session to detect that an admin's password changed after the session began.
     *
     * @param AdminAccount $admin Authenticated administrator.
     * @return string Keyed hash of the stored password hash.
     */
    public static function passwordFingerprint(AdminAccount $admin): string
    {
        return hash_hmac('sha256', (string) $admin->getAuthPassword(), (string) config('app.key'));
    }

    /**
     * Whether the request targets a route protected by the admin guard.
     *
     * @param Request $request Incoming request.
     * @return bool True for `auth:admin` routes under /admin or /superadmin.
     */
    private function isAdminRoute(Request $request): bool
    {
        return $request->is('admin', 'admin/*', 'superadmin', 'superadmin/*')
            && in_array('auth:admin', $request->route()?->gatherMiddleware() ?? [], true);
    }

    /**
     * Compare the session's password fingerprint with the current one, recording it on first sight.
     *
     * A password reset or change therefore ends every other session of the same administrator.
     *
     * @param Request $request Incoming request.
     * @param AdminAccount $admin Authenticated administrator.
     * @return bool False when the password changed since this session recorded its fingerprint.
     */
    private function passwordFingerprintMatches(Request $request, AdminAccount $admin): bool
    {
        $current = self::passwordFingerprint($admin);
        $stored = $request->session()->get('admin_password_fingerprint');

        if (! is_string($stored)) {
            $request->session()->put('admin_password_fingerprint', $current);

            return true;
        }

        return hash_equals($stored, $current);
    }
}
