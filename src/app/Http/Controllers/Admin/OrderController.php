<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Order\DeleteOrderAction;
use App\Actions\Order\UpdateOrderAction;
use App\Actions\Order\UpdateOrderStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $room = $request->attributes->get('room');
        $query = Order::where('room_id', $room->id)->with(['roomUser.globalUser', 'items.toppings', 'campaign'])->latest();
        if ($request->filled('status')) $query->where('status', $request->string('status')->toString());
        if ($request->filled('campaign_id')) $query->where('campaign_id', $request->integer('campaign_id'));
        if ($request->filled('room_user_id')) $query->where('room_user_id', $request->integer('room_user_id'));
        if ($request->filled('from')) $query->whereDate('created_at', '>=', $request->date('from'));
        if ($request->filled('to')) $query->whereDate('created_at', '<=', $request->date('to'));
        return response()->json(['data' => $query->paginate(50)]);
    }

    public function show(Order $order): JsonResponse
    {
        $this->assertRoom($order);
        return response()->json(['data' => $order->load(['roomUser.globalUser', 'items.toppings', 'campaign'])]);
    }

    public function update(UpdateOrderRequest $request, Order $order, UpdateOrderAction $action): JsonResponse
    {
        $this->assertRoom($order);
        return response()->json(['data' => $action->execute($order, $request->validated())]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order, UpdateOrderStatusAction $action): JsonResponse
    {
        $this->assertRoom($order);
        return response()->json(['data' => $action->execute($order, $request->validated('status'))]);
    }

    public function cancel(Order $order, UpdateOrderStatusAction $action): JsonResponse
    {
        $this->assertRoom($order);
        if ($order->status->value === 'cancelled') return response()->json(['data' => $order]);
        return response()->json(['data' => $action->execute($order, 'cancelled')]);
    }

    public function unlock(Order $order, UpdateOrderStatusAction $action): JsonResponse
    {
        $this->assertRoom($order);
        if ($order->status->isActive()) $order = $action->execute($order, 'cancelled');
        return response()->json(['data' => ['unlocked' => true, 'order' => $order]]);
    }

    public function destroy(Order $order, DeleteOrderAction $action): JsonResponse
    {
        $this->assertRoom($order);
        $action->execute($order);
        return response()->json(['message' => 'order_deleted']);
    }

    private function assertRoom(Order $order): void
    {
        abort_unless($order->room_id === request()->attributes->get('room')->id, 404);
    }
}
