<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hide internal JSON endpoints (e.g. realtime socket tokens) from direct browser navigation.
 *
 * The app's own scripts always send "Accept: application/json"; a plain browser visit does not,
 * so it gets a 404 instead of a page exposing a live token.
 */
class EnsureJsonRequest
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request Incoming request.
     * @param Closure(Request): Response $next Next middleware.
     * @return Response Downstream response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->expectsJson(), 404);

        return $next($request);
    }
}
