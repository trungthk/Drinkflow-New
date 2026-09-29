<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Permission;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route guard for a Superadmin feature: `permission:agent.view`.
 *
 * Checks the feature Gate for the signed-in superadmin; resource-level scope checks are done by
 * the policies and AgentScope inside the controllers.
 */
class EnsureSuperadminPermission
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request Incoming request.
     * @param Closure(Request): Response $next Next handler.
     * @param string $permission Permission key (App\Enums\Permission value).
     * @return Response Downstream response.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        abort_unless(Gate::forUser($request->user('superadmin'))->allows(Permission::from($permission)->value), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
