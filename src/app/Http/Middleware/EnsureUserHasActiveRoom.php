<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\RoomUserStatus;
use App\Models\GlobalUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Require an active room membership for financial and analytics pages. */
class EnsureUserHasActiveRoom
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request Current request.
     * @param Closure $next Next middleware.
     * @return Response Response from the next middleware or a denial response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var GlobalUser|null $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');
        if ($user instanceof GlobalUser && $user->roomUsers()->where('status', RoomUserStatus::Active->value)->exists()) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['message' => __('global.rooms.no_rooms_access'), 'data' => []], 403);
        }

        return redirect()->route('user.me.dashboard')->with('status_info', __('global.rooms.join_first_prompt'));
    }
}
