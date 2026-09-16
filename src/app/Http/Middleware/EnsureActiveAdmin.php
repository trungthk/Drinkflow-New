<?php

declare(strict_types=1);

namespace App\Http\Middleware;

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

        return $next($request);
    }
}
