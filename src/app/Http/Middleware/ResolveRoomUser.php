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
        $room = $room instanceof Room ? $room : Room::where('slug', $room)->orWhere('id', is_numeric($room) ? (int)$room : 0)->firstOrFail();
        $globalUser = $request->attributes->get('global_user') ?? $request->user('web');

        if (!$globalUser) {
            if ($request->expectsJson()) {
                abort(401, 'Unauthenticated.');
            }
            return redirect()->guest(route('auth.google'));
        }

        $roomUser = $globalUser->roomUsers()->where('room_id', $room->id)->first();

        if (!$roomUser) {
            if ($request->expectsJson()) {
                abort(403, 'Requires room membership.');
            }
            return redirect()->route('user.rooms.join.show', $room->slug);
        }

        $roomUserStatus = $roomUser->status instanceof \BackedEnum ? $roomUser->status->value : (string) $roomUser->status;
        if ($roomUserStatus === 'blocked') {
            if ($request->expectsJson()) {
                abort(403, 'Tài khoản của bạn trong Room này đã bị khóa.');
            }
            $adminUser = $room->admins()->first();
            return response()->view('user.blocked-room', [
                'room' => $room,
                'roomUser' => $roomUser,
                'adminUser' => $adminUser,
            ], 403);
        }

        abort_unless($roomUserStatus === 'active', 403);

        $deviceUuid = (string) $request->cookie('drinkflow_device_uuid', '');
        $token = (string) $request->cookie('drinkflow_trusted_token', '');
        if ($deviceUuid !== '' || $token !== '') {
            $device = app(DeviceTrustService::class)->resolve($deviceUuid, $token, $room->id);
            if ($device && $device->room_user_id !== $roomUser->id) {
                abort(403, 'Device binding mismatch.');
            }
        }

        $roomUser->update(['last_active_at' => now()]);
        $request->attributes->set('room', $room);
        $request->attributes->set('room_user', $roomUser);

        return $next($request);
    }
}
