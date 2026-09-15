<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\RoomStatus;
use App\Models\Room;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRoomAccess
{
    /**
     * Bảo đảm admin đang active có quyền truy cập vào phòng đang hoạt động.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): \Symfony\Component\HttpFoundation\Response  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user('admin');
        $room = $request->route('room');
        if (! $room instanceof Room) {
            $room = (new Room())->resolveRouteBinding($room);
        }
        abort_unless($room instanceof Room, 404);

        abort_unless(
            $admin && $admin->isActive() && ($admin->isSuperadmin() || $admin->rooms()->whereKey($room->id)->exists()),
            403
        );

        abort_unless($room->status === RoomStatus::Active, 404);
        $request->route()->setParameter('room', $room);
        $request->attributes->set('room', $room);

        return $next($request);
    }
}
