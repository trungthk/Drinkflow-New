<?php

namespace App\Http\Controllers\User;

use App\Actions\User\JoinRoomAction;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\Auth\DeviceTrustService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Contracts\View\View;

class RoomController extends Controller
{
    /**
     * Handle the show operation.
     * @param Request $request Parameter value.
     * @param Room $room Parameter value.
     * @return JsonResponse|RedirectResponse|View Result of the operation.
     */
    public function show(Request $request, Room $room): JsonResponse|RedirectResponse|View
    {
        abort_unless($room->status === 'active', 404);
        $user = $request->user('web');
        if (! $user) {
            $device = app(DeviceTrustService::class)->resolve(
                (string) $request->cookie('drinkflow_device_uuid', ''),
                (string) $request->cookie('drinkflow_trusted_token', ''),
                $room->id,
            );
            $user = $device?->roomUser?->globalUser;
            if ($user) {
                auth('web')->login($user, true);
            }
        }
        if (! $user) {
            $request->session()->put('url.intended', url()->current());
            if ($request->expectsJson()) {
                return response()->json(['requires_authentication' => true, 'redirect' => route('landing')], 401);
            }
            $referer = $request->headers->get('referer') ?: url()->previous();
            if ($referer && $referer !== $request->fullUrl() && $referer !== $request->url() && $referer !== url('/')) {
                return redirect()->to($referer);
            }
            return redirect()->to('/');
        }
        $userStatus = $user->status instanceof \BackedEnum ? $user->status->value : (string) ($user->status ?? 'active');
        abort_unless($userStatus === 'active', 403);

        $membership = $user->roomUsers()->where('room_id', $room->id)->first();
        if (! $membership) {
            if ($request->expectsJson()) {
                return response()->json([
                    'requires_confirmation' => true,
                    'profile' => $user->only(['name', 'email', 'avatar_url']),
                    'room' => $room->only(['id', 'name', 'slug', 'description', 'avatar_url']),
                ]);
            }
            return view('user.join-room', [
                'room' => $room,
                'user' => $user,
                'adminUser' => $room->admins()->first(),
            ]);
        }

        $memStatus = $membership->status instanceof \BackedEnum ? $membership->status->value : (string) $membership->status;
        if ($memStatus === 'blocked') {
            if ($request->expectsJson()) {
                abort(403, 'Tài khoản bị khóa.');
            }
            return response()->view('user.blocked-room', [
                'room' => $room,
                'roomUser' => $membership,
                'adminUser' => $room->admins()->first(),
            ], 403);
        }

        abort_unless($memStatus === 'active', 403);

        if (! $request->expectsJson()) {
            return redirect()->route('user.dashboard', $room->slug);
        }

        return response()->json(['data' => $room->loadCount(['campaigns']), 'room_user' => $membership]);
    }

    /**
     * Handle the join operation.
     * @param Request $request Parameter value.
     * @param Room $room Parameter value.
     * @param JoinRoomAction $action Parameter value.
     * @param DeviceTrustService $devices Parameter value.
     * @return JsonResponse|RedirectResponse Result of the operation.
     */
    public function join(Request $request, Room $room, JoinRoomAction $action, DeviceTrustService $devices): JsonResponse|RedirectResponse
    {
        abort_unless($room->status === 'active', 404);
        $user = $request->user('web');
        $userStatus = $user ? ($user->status instanceof \BackedEnum ? $user->status->value : (string) ($user->status ?? 'active')) : null;
        abort_unless($user && $userStatus === 'active', 401);
        $deviceUuid = (string) ($request->cookie('drinkflow_device_uuid') ?: Str::uuid());
        $roomUser = $action->execute($user, $room, $deviceUuid, hash('sha256', Str::random(64)));
        $token = $devices->issue($roomUser, $deviceUuid);

        if (! $request->expectsJson()) {
            return redirect()->route('user.dashboard', $room->slug)
                ->withCookie(cookie('drinkflow_device_uuid', $deviceUuid, 60 * 24 * 365, '/', null, $request->isSecure(), true, 'lax'))
                ->withCookie(cookie('drinkflow_trusted_token', $token, 60 * 24 * 30, '/', null, $request->isSecure(), true, 'lax'));
        }

        return response()->json(['data' => $roomUser->load('room'), 'redirect' => route('user.campaigns.index', $room->slug)])
            ->withCookie(cookie('drinkflow_device_uuid', $deviceUuid, 60 * 24 * 365, '/', null, $request->isSecure(), true, 'lax'))
            ->withCookie(cookie('drinkflow_trusted_token', $token, 60 * 24 * 30, '/', null, $request->isSecure(), true, 'lax'));
    }
}
