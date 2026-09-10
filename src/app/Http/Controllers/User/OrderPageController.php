<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Room;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OrderPageController extends Controller
{
    public function __invoke(Request $request, Room $room, Order $order): View
    {
        $roomUser = $request->attributes->get('room_user');
        abort_unless($order->room_id === $room->id && $order->room_user_id === $roomUser->id, 404);

        return view('user.order', ['room' => $room, 'order' => $order->load(['items.toppings', 'campaign'])]);
    }
}
