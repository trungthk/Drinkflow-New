<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Actions\User\JoinRoomAction;
use App\Enums\GlobalUserStatus;
use App\Enums\RoomStatus;
use App\Enums\RoomUserStatus;
use App\Http\Controllers\Controller;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Services\Auth\DeviceTrustService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RoomController extends Controller
{
    /**
     * Show the room entry point, handling auth, membership check, and blocked states.
     *
     * @param Request $request Current HTTP request.
     * @param Room $room Target room model resolved by slug/id.
     * @param DeviceTrustService $devices Device trust verification service.
     * @return JsonResponse|RedirectResponse|View|Response Rendered view, redirect, or JSON payload.
     */
    public function show(Request $request, Room $room, DeviceTrustService $devices): JsonResponse|RedirectResponse|View|Response
    {
        abort_unless($room->status === RoomStatus::Active->value, 404);

        /** @var GlobalUser|null $user */
        $user = $request->user('web');
        if (! $user) {
            $device = $devices->resolve(
                (string) $request->cookie('drinkflow_device_uuid', ''),
                (string) $request->cookie('drinkflow_trusted_token', ''),
                $room->id,
            );
            $user = $device?->roomUser?->globalUser;
            if ($user) {
                Auth::guard('web')->login($user, true);
            }
        }

        if (! $user) {
            $request->session()->put('url.intended', url()->current());
            if ($request->expectsJson()) {
                return response()->json(['requires_authentication' => true, 'redirect' => route('landing')], 401);
            }
            return redirect()->to('/')->with('auth_notice', __('public.auth_modal.require_login_room', ['room' => $room->name]));
        }

        $userStatus = $user->status instanceof \BackedEnum ? $user->status->value : (string) ($user->status ?? GlobalUserStatus::Active->value);
        abort_unless($userStatus === GlobalUserStatus::Active->value, 403);

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
        if ($memStatus === RoomUserStatus::Blocked->value) {
            if ($request->expectsJson()) {
                abort(403, __('global.blocked.account_blocked'));
            }
            return response()->view('user.blocked-room', [
                'room' => $room,
                'roomUser' => $membership,
                'adminUser' => $room->admins()->first(),
            ], 403);
        }

        abort_unless($memStatus === RoomUserStatus::Active->value, 403);

        if (! $request->expectsJson()) {
            return redirect()->route('user.dashboard', $room->slug);
        }

        return response()->json(['data' => $room->loadCount(['campaigns']), 'room_user' => $membership]);
    }

    /**
     * Join the current user to the specified room.
     *
     * @param Request $request Current HTTP request.
     * @param Room $room Target room model.
     * @param JoinRoomAction $action Join room domain action.
     * @param DeviceTrustService $devices Device trust issuance service.
     * @return JsonResponse|RedirectResponse Redirect to dashboard or JSON response.
     */
    public function join(Request $request, Room $room, JoinRoomAction $action, DeviceTrustService $devices): JsonResponse|RedirectResponse
    {
        abort_unless($room->status === RoomStatus::Active->value, 404);

        /** @var GlobalUser|null $user */
        $user = $request->user('web');
        $userStatus = $user ? ($user->status instanceof \BackedEnum ? $user->status->value : (string) ($user->status ?? GlobalUserStatus::Active->value)) : null;
        abort_unless($user && $userStatus === GlobalUserStatus::Active->value, 401);

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
