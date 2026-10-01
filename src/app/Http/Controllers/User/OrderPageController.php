<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Room;
use App\Models\RoomUser;
use App\Services\Order\MemberOrderPageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Single order page of a member, addressed by the order code:
 * /rooms/{room}/orders/{order:code}/view.
 */
class OrderPageController extends Controller
{
    /**
     * Show one order of the signed-in member in the room, rendered by the same view as the order
     * list so both pages stay identical.
     *
     * The URL carries the order code rather than the id, so links survive being shared or stored
     * in notifications.
     *
     * @param Request $request Current HTTP request.
     * @param Room $room Room the order belongs to.
     * @param Order $order Order resolved from its code.
     * @param MemberOrderPageService $orderPage Builds the shared list/order-page view data.
     * @return View Order page.
     */
    public function __invoke(Request $request, Room $room, Order $order, MemberOrderPageService $orderPage): View
    {
        /** @var RoomUser $roomUser */
        $roomUser = $request->attributes->get('room_user');
        $canViewOrder = $order->room_user_id === $roomUser->id
            || $order->parent()->where('room_user_id', $roomUser->id)->exists();
        abort_unless($order->room_id === $room->id && $canViewOrder, 404);

        return view('user.orders', $orderPage->build($room, $roomUser, $order));
    }
}
