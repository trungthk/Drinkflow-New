<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\System\SystemSettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Routes that stay reachable during maintenance so a superadmin can still sign in
     * (including password recovery) and anyone can switch the maintenance page language.
     *
     * @var list<string>
     */
    private const ALWAYS_ALLOWED_ROUTES = [
        'admin.login.page', 'admin.login', 'admin.login.two-factor.cancel', 'admin.logout',
        'admin.forgot-password.page', 'admin.forgot-password.send',
        'admin.verify-otp.page', 'admin.verify-otp.submit',
        'admin.reset-password.page', 'admin.reset-password.submit',
        'locale.switch',
    ];

    /** Google OAuth routes, allowed only while an admin is completing Google two-factor sign-in. */
    private const ADMIN_GOOGLE_2FA_ROUTES = ['auth.google', 'auth.google.callback'];

    public function __construct(private readonly SystemSettingsService $settings)
    {
    }

    /**
     * Show the maintenance page to public visitors, users and room admins while maintenance is active.
     *
     * Superadmins keep access to the admin console (their layout shows a warning banner instead). Runs in the `web`
     * group so the session, the admin guard and the matched route are available.
     *
     * @param Request $request Incoming request.
     * @param Closure(Request): Response $next Next middleware handler.
     * @return Response 503 maintenance page/JSON, or the downstream response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $state = $this->settings->maintenanceState();
        if (! $state['active'] || $this->bypasses($request)) {
            return $next($request);
        }

        $headers = $state['ends'] !== null && $state['ends']->isFuture()
            ? ['Retry-After' => (string) max(1, (int) now()->diffInSeconds($state['ends']))]
            : [];

        if ($request->expectsJson()) {
            return response()->json(['message' => __('errors.maintenance.json_message')], Response::HTTP_SERVICE_UNAVAILABLE, $headers);
        }

        return response()->view('errors.maintenance', ['maintenance' => $state], Response::HTTP_SERVICE_UNAVAILABLE, $headers);
    }

    /**
     * Whether this request may proceed despite active maintenance.
     *
     * @param Request $request Incoming request.
     * @return bool True for superadmins inside the admin console and for the sign-in/locale routes.
     */
    private function bypasses(Request $request): bool
    {
        // The admin session cookie is shared with the public and user areas, so a superadmin only
        // bypasses maintenance in the admin console; public/user pages show maintenance to everyone.
        if ($request->is('admin', 'admin/*', 'superadmin', 'superadmin/*') && $request->user('admin')?->isSuperadmin()) {
            return true;
        }

        $routeName = $request->route()?->getName();
        if (in_array($routeName, self::ALWAYS_ALLOWED_ROUTES, true)) {
            return true;
        }

        return in_array($routeName, self::ADMIN_GOOGLE_2FA_ROUTES, true)
            && $request->hasSession()
            && $request->session()->has('admin_google_2fa_admin_id');
    }
}
