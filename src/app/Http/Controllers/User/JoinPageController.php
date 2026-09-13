<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Enums\RoomStatus;
use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class JoinPageController extends Controller
{
    /**
     * Render the room joining confirmation page.
     *
     * @param Request $request Current HTTP request instance.
     * @param Room $room Target room model resolved by slug/id.
     * @return View Join room view response.
     */
    public function __invoke(Request $request, Room $room): View
    {
        abort_unless($room->status === RoomStatus::Active->value, 404);

        if (! $request->user('web')) {
            $request->session()->put('url.intended', url()->current());
        }

        return view('user.join-room', ['room' => $room, 'user' => $request->user('web')]);
    }
}
