<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Room;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OrderPageController extends Controller
{
    /**
     * Hiển thị trang chi tiết đơn hàng cá nhân trong phòng (/rooms/{room}/orders/{order}).
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request hiện tại
     * @param  \App\Models\Room  $room  Đối tượng phòng
     * @param  \App\Models\Order  $order  Đối tượng đơn hàng cá nhân
     * @return \Illuminate\Contracts\View\View  Giao diện chi tiết đơn hàng
     */
    public function __invoke(Request $request, Room $room, Order $order): View
    {
        $roomUser = $request->attributes->get('room_user');
        abort_unless($order->room_id === $room->id && $order->room_user_id === $roomUser->id, 404);

        return view('user.order', ['room' => $room, 'order' => $order->load(['items.toppings', 'campaign'])]);
    }
}
