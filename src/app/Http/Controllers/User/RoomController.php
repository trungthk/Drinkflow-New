<?php

namespace App\Http\Controllers\User;

use App\Actions\User\JoinRoomAction;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\Auth\DeviceTrustService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoomController extends Controller
{
    /**
     * Handle the show operation.
     * @param Request $request Parameter value.
     * @param Room $room Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function show(Request $request, Room $room): JsonResponse
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

            return response()->json(['requires_authentication' => true, 'redirect' => route('auth.google')], 401);
        }
        abort_unless($user->status?->value === 'active', 403);

        $membership = $user->roomUsers()->where('room_id', $room->id)->first();
        if (! $membership) {
            return response()->json([
                'requires_confirmation' => true,
                'profile' => $user->only(['name', 'email', 'avatar_url']),
                'room' => $room->only(['id', 'name', 'slug', 'description', 'avatar_url']),
            ]);
        }
        abort_unless($membership->status?->value === 'active', 403);

        return response()->json(['data' => $room->loadCount(['campaigns']), 'room_user' => $membership]);
    }

    /**
     * Handle the join operation.
     * @param Request $request Parameter value.
     * @param Room $room Parameter value.
     * @param JoinRoomAction $action Parameter value.
     * @param DeviceTrustService $devices Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function join(Request $request, Room $room, JoinRoomAction $action, DeviceTrustService $devices): JsonResponse
    {
        abort_unless($room->status === 'active', 404);
        $user = $request->user('web');
        abort_unless($user && $user->status?->value === 'active', 401);
        $deviceUuid = (string) ($request->cookie('drinkflow_device_uuid') ?: Str::uuid());
        $roomUser = $action->execute($user, $room, $deviceUuid, hash('sha256', Str::random(64)));
        $token = $devices->issue($roomUser, $deviceUuid);

        if (! $request->expectsJson()) {
            return redirect()->route('user.dashboard', $room)
                ->withCookie(cookie('drinkflow_device_uuid', $deviceUuid, 60 * 24 * 365, '/', null, $request->isSecure(), true, 'lax'))
                ->withCookie(cookie('drinkflow_trusted_token', $token, 60 * 24 * 30, '/', null, $request->isSecure(), true, 'lax'));
        }

        return response()->json(['data' => $roomUser->load('room'), 'redirect' => route('user.campaigns.index', $room)])
            ->withCookie(cookie('drinkflow_device_uuid', $deviceUuid, 60 * 24 * 365, '/', null, $request->isSecure(), true, 'lax'))
            ->withCookie(cookie('drinkflow_trusted_token', $token, 60 * 24 * 30, '/', null, $request->isSecure(), true, 'lax'));
    }
}
