<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Auth\DeviceTrustService;

class ResolveGlobalUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');

        // A trusted device is sufficient to restore the global session. The
        // token is always checked against its hash; device_uuid alone is not
        // treated as an authentication credential.
        if (!$user) {
            $deviceUuid = (string) $request->cookie('drinkflow_device_uuid', '');
            $token = (string) $request->cookie('drinkflow_trusted_token', '');
            $room = $request->route('room');
            $roomId = is_object($room) ? $room->id : (is_numeric($room) ? (int) $room : null);
            $device = app(DeviceTrustService::class)->resolve($deviceUuid, $token, $roomId);
            if ($device?->roomUser?->globalUser) {
                $user = $device->roomUser->globalUser;
                auth('web')->login($user, true);
            }
        }
        if (!$user) {
            if ($request->expectsJson()) {
                abort(401, 'Unauthenticated.');
            }
            return redirect()->guest(route('auth.google'));
        }
        if ($user->status?->value === 'blocked') {
            $request->attributes->set('global_user', $user);
            if ($request->routeIs('user.blocked', 'user.blocked.appeal', 'logout')) {
                return $next($request);
            }
            if ($request->expectsJson()) {
                abort(403, 'Tài khoản của bạn tạm thời bị khóa.');
            }
            return redirect()->route('user.blocked');
        }

        abort_unless($user->status?->value === 'active', 403);
        $request->attributes->set('global_user', $user);
        return $next($request);
    }
}
