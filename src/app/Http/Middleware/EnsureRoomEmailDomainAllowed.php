<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\GlobalUser;
use App\Models\Room;
use App\Services\Room\RoomAccessPolicy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforce a room's allowed email domains on its join routes (alias "room.email_domain").
 * Guests pass through (the join flow asks them to sign in first); JoinRoomAction checks the domain again.
 */
class EnsureRoomEmailDomainAllowed
{
    /**
     * Create the middleware.
     *
     * @param RoomAccessPolicy $policy Room access rules.
     */
    public function __construct(private readonly RoomAccessPolicy $policy)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request Incoming request.
     * @param Closure(Request): Response $next Next handler.
     * @return Response Response.
     * @throws \App\Exceptions\RoomAccessDeniedException When the account's email domain is not allowed.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $room = EnsureRoomIpAllowed::room($request);
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        if ($room instanceof Room && $user instanceof GlobalUser) {
            $this->policy->ensureEmailAllowed($room, $user);
        }

        return $next($request);
    }
}
