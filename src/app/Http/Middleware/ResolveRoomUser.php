<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Room;
use App\Services\Auth\DeviceTrustService;

class ResolveRoomUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $room = $request->route('room');
        $room = $room instanceof Room ? $room : Room::whereKey($room)->firstOrFail();
        $globalUser = $request->attributes->get('global_user') ?? $request->user('web');
        $roomUser = $globalUser?->roomUsers()->where('room_id', $room->id)->first();
        abort_unless($roomUser && $roomUser->status?->value === 'active', 403);
        $deviceUuid = (string) $request->cookie('drinkflow_device_uuid', '');
        $token = (string) $request->cookie('drinkflow_trusted_token', '');
        if ($deviceUuid !== '' || $token !== '') {
            $device = app(DeviceTrustService::class)->resolve($deviceUuid, $token, $room->id);
            abort_unless($device && $device->room_user_id === $roomUser->id, 403);
        }
        $roomUser->update(['last_active_at' => now()]);
        $request->attributes->set('room', $room);
        $request->attributes->set('room_user', $roomUser);
        return $next($request);
    }
}
