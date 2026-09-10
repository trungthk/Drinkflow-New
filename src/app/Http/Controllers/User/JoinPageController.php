<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class JoinPageController extends Controller
{
    public function __invoke(Request $request, Room $room): View
    {
        abort_unless($room->status === 'active', 404);
        if (! $request->user('web')) {
            $request->session()->put('url.intended', url()->current());
        }

        return view('user.join-room', ['room' => $room, 'user' => $request->user('web')]);
    }
}
