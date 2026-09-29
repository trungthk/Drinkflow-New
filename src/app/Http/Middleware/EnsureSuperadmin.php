<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\SuperadminStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allow only an active Superadmin signed in on the `superadmin` guard.
 */
class EnsureSuperadmin
{
    /**
     * Handle an incoming request.
     *
     * A suspended superadmin is signed out immediately instead of keeping a live session.
     *
     * @param Request $request Incoming request.
     * @param Closure(Request): Response $next Next handler.
     * @return Response Downstream response, login redirect, or 403 for JSON.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $superadmin = $request->user('superadmin');
        if ($superadmin !== null && $superadmin->status === SuperadminStatus::Active) {
            return $next($request);
        }

        if ($superadmin !== null) {
            Auth::guard('superadmin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
        abort_if($request->expectsJson(), Response::HTTP_FORBIDDEN);

        return redirect()->route('superadmin.login.page');
    }
}
