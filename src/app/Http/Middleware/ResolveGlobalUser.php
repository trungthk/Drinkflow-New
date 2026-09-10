<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveGlobalUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');
        abort_unless($user && $user->status?->value === 'active', 403);
        $request->attributes->set('global_user', $user);
        return $next($request);
    }
}
