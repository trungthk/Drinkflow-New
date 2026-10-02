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

        // RoomPolicy::operate: the active owner or a collaborator, never another Agent's room.
        if (! ($admin && \Illuminate\Support\Facades\Gate::forUser($admin)->allows('operate', $room))) {
            if ($request->expectsJson()) {
                abort(Response::HTTP_FORBIDDEN);
            }

            return redirect()->route('admin.login.page')->with('admin_access_denied', true);
        }

        abort_unless($room->status === RoomStatus::Active, 404);
        $request->route()->setParameter('room', $room);
        $request->attributes->set('room', $room);
        // Account pages without a room (my rooms, subscription, billing, profile) keep this room's menu.
        if ($request->hasSession()) {
            $request->session()->put(\App\View\Composers\AdminLayoutComposer::CONTEXT_ROOM_SESSION_KEY, $room->id);
        }

        return $next($request);
    }
}
