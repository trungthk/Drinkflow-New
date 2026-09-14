<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\GlobalUserStatus;
use App\Services\Auth\DeviceTrustService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveGlobalUser
{
    /**
     * Xác định GlobalUser từ session hoặc trusted device cookie và bảo vệ route.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): \Symfony\Component\HttpFoundation\Response  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');
        $deviceUuid = (string) $request->cookie('drinkflow_device_uuid', '');
        $token = (string) $request->cookie('drinkflow_trusted_token', '');

        // A revoked trusted-device token must also invalidate an otherwise
        // still-present Laravel session. Without this check, a revoked device
        // can retain its web session or have it restored on the next refresh.
        if ($user && ($deviceUuid !== '' || $token !== '')) {
            $device = app(DeviceTrustService::class)->resolve($deviceUuid, $token);
            if (! $device || $device->roomUser?->global_user_id !== $user->id) {
                auth('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                $user = null;
            }
        }

        // A trusted device is sufficient to restore the global session. The
        // token is always checked against its hash; device_uuid alone is not
        // treated as an authentication credential.
        if (! $user) {
            $room       = $request->route('room');
            $roomId     = is_object($room) ? $room->id : (is_numeric($room) ? (int) $room : null);
            $device     = app(DeviceTrustService::class)->resolve($deviceUuid, $token, $roomId);
            if ($device?->roomUser?->globalUser) {
                $user = $device->roomUser->globalUser;
                auth('web')->login($user, true);
            }
        }

        if (! $user) {
            if ($request->expectsJson()) {
                abort(401, 'Unauthenticated.');
            }
            $referer = $request->headers->get('referer') ?: url()->previous();
            if ($referer && $referer !== $request->fullUrl() && $referer !== $request->url() && $referer !== url('/')) {
                return redirect()->to($referer);
            }

            return redirect()->to('/');
        }

        // Status is cast to GlobalUserStatus enum via model casts.
        $status = $user->status instanceof GlobalUserStatus
            ? $user->status
            : GlobalUserStatus::from((string) ($user->status?->value ?? $user->status ?? ''));

        if ($status === GlobalUserStatus::Blocked) {
            $request->attributes->set('global_user', $user);
            if ($request->routeIs('user.blocked', 'user.blocked.appeal', 'logout')) {
                return $next($request);
            }
            if ($request->expectsJson()) {
                abort(403, 'Tài khoản của bạn tạm thời bị khóa.');
            }

            return redirect()->route('user.blocked');
        }

        abort_unless($status === GlobalUserStatus::Active, 403);
        $request->attributes->set('global_user', $user);

        return $next($request);
    }
}
