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
    /**
     * Handle the index operation.
     * @param Request $request Parameter value.
     * @return JsonResponse Result of the operation.
     */
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

    /**
     * Handle the show operation.
     * @param Order $order Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function show(Order $order): JsonResponse
    {
        $this->assertRoom($order);
        return response()->json(['data' => $order->load(['roomUser.globalUser', 'items.toppings', 'campaign'])]);
    }

    /**
     * Handle the update operation.
     * @param UpdateOrderRequest $request Parameter value.
     * @param Order $order Parameter value.
     * @param UpdateOrderAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function update(UpdateOrderRequest $request, Order $order, UpdateOrderAction $action): JsonResponse
    {
        $this->assertRoom($order);
        return response()->json(['data' => $action->execute($order, $request->validated())]);
    }

    /**
     * Handle the update status operation.
     * @param UpdateOrderStatusRequest $request Parameter value.
     * @param Order $order Parameter value.
     * @param UpdateOrderStatusAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order, UpdateOrderStatusAction $action): JsonResponse
    {
        $this->assertRoom($order);
        return response()->json(['data' => $action->execute($order, $request->validated('status'))]);
    }

    /**
     * Handle the cancel operation.
     * @param Order $order Parameter value.
     * @param UpdateOrderStatusAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function cancel(Order $order, UpdateOrderStatusAction $action): JsonResponse
    {
        $this->assertRoom($order);
        if ($order->status->value === 'cancelled') return response()->json(['data' => $order]);
        return response()->json(['data' => $action->execute($order, 'cancelled')]);
    }

    /**
     * Handle the unlock operation.
     * @param Order $order Parameter value.
     * @param UpdateOrderStatusAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function unlock(Order $order, UpdateOrderStatusAction $action): JsonResponse
    {
        $this->assertRoom($order);
        if ($order->status->isActive()) $order = $action->execute($order, 'cancelled');
        return response()->json(['data' => ['unlocked' => true, 'order' => $order]]);
    }

    /**
     * Handle the destroy operation.
     * @param Order $order Parameter value.
     * @param DeleteOrderAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function destroy(Order $order, DeleteOrderAction $action): JsonResponse
    {
        $this->assertRoom($order);
        $action->execute($order);
        return response()->json(['message' => 'order_deleted']);
    }

    /**
     * Handle the assert room operation.
     * @param Order $order Parameter value.
     * @return void Result of the operation.
     */
    private function assertRoom(Order $order): void
    {
        abort_unless($order->room_id === request()->attributes->get('room')->id, 404);
    }
}
