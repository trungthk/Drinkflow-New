<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Room;

class ResolveRoomUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $room = $request->route('room');
        $room = $room instanceof Room ? $room : Room::whereKey($room)->firstOrFail();
        $globalUser = $request->attributes->get('global_user') ?? $request->user('web');
        $roomUser = $globalUser?->roomUsers()->where('room_id', $room->id)->first();
        abort_unless($roomUser && $roomUser->status?->value === 'active', 403);
        $request->attributes->set('room', $room);
        $request->attributes->set('room_user', $roomUser);
        return $next($request);
    }
}
