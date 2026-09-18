<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Enums\RoomStatus;
use App\Enums\GlobalUserStatus;
use App\Enums\RoomUserStatus;
use App\Http\Controllers\Controller;
use App\Models\GlobalUser;
use App\Models\Room;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class JoinPageController extends Controller
{
    /**
     * Render the room joining confirmation page.
     *
     * @param Request $request Current HTTP request instance.
     * @param Room $room Target room model resolved by slug/id.
     * @return View|RedirectResponse Join confirmation or redirect to the room/homepage.
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException When the room or user is inactive.
     */
    public function __invoke(Request $request, Room $room): View|RedirectResponse
    {
        $user = $request->user('web');
        if (! $user instanceof GlobalUser) {
            return redirect()->route('landing');
        }

        abort_unless($user->status === GlobalUserStatus::Active, 403);
        $roomStatus = $room->status instanceof \BackedEnum ? $room->status->value : (string) $room->status;
        abort_unless($roomStatus === RoomStatus::Active->value, 404);

        $membership = $user->roomUsers()->where('room_id', $room->id)->first();
        if ($membership?->status === RoomUserStatus::Active || $membership?->status === RoomUserStatus::Blocked) {
            return redirect()->route('user.rooms.show', $room->slug);
        }

        return view('user.join-room', ['room' => $room, 'user' => $user]);
    }
}
