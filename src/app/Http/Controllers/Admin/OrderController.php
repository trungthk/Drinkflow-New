<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Order\DeleteOrderAction;
use App\Actions\Order\UpdateOrderAction;
use App\Actions\Order\UpdateOrderStatusAction;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Campaign;
use App\Models\Order;
use App\Models\Room;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of orders as JSON.
     *
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
     * Display the standalone Orders & Price Adjustments management page.
     *
     * @param Request $request Incoming request.
     * @param Room $room Room entity.
     * @return View Blade view.
     */
    public function page(Request $request, Room $room): View
    {
        $query = Order::where('room_id', $room->id)
            ->with(['roomUser.globalUser', 'items.toppings', 'campaign'])
            ->latest();

        $search = trim($request->string('search')->toString());
        if ($search !== '') {
            $normalizedSearch = mb_strtolower($search);
            $query->where(function ($orderQuery) use ($normalizedSearch, $search): void {
                if (ctype_digit($search)) {
                    $orderQuery->orWhere('id', (int) $search);
                }
                $orderQuery
                    ->orWhereRaw('LOWER(note) LIKE ?', ['%' . $normalizedSearch . '%'])
                    ->orWhereHas('campaign', function ($campaignQuery) use ($normalizedSearch): void {
                        $campaignQuery->whereRaw('LOWER(name) LIKE ?', ['%' . $normalizedSearch . '%'])
                            ->orWhereRaw('LOWER(restaurant) LIKE ?', ['%' . $normalizedSearch . '%']);
                    })
                    ->orWhereHas('roomUser', function ($roomUserQuery) use ($normalizedSearch): void {
                        $roomUserQuery->whereRaw('LOWER(display_name) LIKE ?', ['%' . $normalizedSearch . '%'])
                            ->orWhereRaw('LOWER(user_code) LIKE ?', ['%' . $normalizedSearch . '%'])
                            ->orWhereHas('globalUser', function ($userQuery) use ($normalizedSearch): void {
                                $userQuery->whereRaw('LOWER(name) LIKE ?', ['%' . $normalizedSearch . '%'])
                                    ->orWhereRaw('LOWER(email) LIKE ?', ['%' . $normalizedSearch . '%']);
                            });
                    });
            });
        }

        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->integer('campaign_id'));
        }

        if ($request->filled('status') && $request->string('status')->toString() !== 'all') {
            $query->where('status', $request->string('status')->toString());
        }

        $orders = $query->paginate(50)->withQueryString();

        $campaigns = Campaign::where('room_id', $room->id)->latest()->get();

        return view('admin.orders', [
            'room' => $room,
            'orders' => $orders,
            'campaigns' => $campaigns,
            'statusFilters' => collect(OrderStatus::cases())->map(static fn (OrderStatus $status): array => [
                'value' => $status->value,
                'label' => __('admin.status_'.match ($status) {
                    OrderStatus::Submitted => 'pending',
                    OrderStatus::Completed => 'paid',
                    default => $status->value,
                }),
            ])->values()->all(),
            'filters' => [
                'search' => $search,
                'campaign_id' => $request->integer('campaign_id') ?: '',
                'status' => $request->string('status')->toString() ?: 'all',
            ],
        ]);
    }

    /**
     * Handle the show operation.
     * @param Room $room Parameter value.
     * @param Order $order Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function show(Room $room, Order $order): JsonResponse
    {
        $this->assertRoom($order);
        return response()->json(['data' => $order->load(['roomUser.globalUser', 'items.toppings', 'campaign'])]);
    }

    /**
     * Handle the update operation.
     * @param UpdateOrderRequest $request Parameter value.
     * @param Room $room Parameter value.
     * @param Order $order Parameter value.
     * @param UpdateOrderAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function update(UpdateOrderRequest $request, Room $room, Order $order, UpdateOrderAction $action): JsonResponse
    {
        $this->assertRoom($order);
        return response()->json(['data' => $action->execute($order, $request->validated())]);
    }

    /**
     * Handle the update status operation.
     * @param UpdateOrderStatusRequest $request Parameter value.
     * @param Room $room Parameter value.
     * @param Order $order Parameter value.
     * @param UpdateOrderStatusAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Room $room, Order $order, UpdateOrderStatusAction $action): JsonResponse
    {
        $this->assertRoom($order);
        return response()->json(['data' => $action->execute($order, $request->validated('status'))]);
    }

    /**
     * Handle the cancel operation.
     * @param Room $room Parameter value.
     * @param Order $order Parameter value.
     * @param UpdateOrderStatusAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function cancel(Room $room, Order $order, UpdateOrderStatusAction $action): JsonResponse
    {
        $this->assertRoom($order);
        if ($order->status->value === 'cancelled') return response()->json(['data' => $order]);
        return response()->json(['data' => $action->execute($order, 'cancelled')]);
    }

    /**
     * Handle the unlock operation.
     * @param Room $room Parameter value.
     * @param Order $order Parameter value.
     * @param UpdateOrderStatusAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function unlock(Room $room, Order $order, UpdateOrderStatusAction $action): JsonResponse
    {
        $this->assertRoom($order);
        if ($order->status->isActive()) $order = $action->execute($order, 'cancelled');
        return response()->json(['data' => ['unlocked' => true, 'order' => $order]]);
    }

    /**
     * Handle the destroy operation.
     * @param Room $room Parameter value.
     * @param Order $order Parameter value.
     * @param DeleteOrderAction $action Parameter value.
     * @return JsonResponse Result of the operation.
     */
    public function destroy(Room $room, Order $order, DeleteOrderAction $action): JsonResponse
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
