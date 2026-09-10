<?php

namespace App\Http\Controllers\User;

use App\Actions\Order\CreateOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Campaign;
use App\Models\Order;
use App\Models\Room;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $roomUser = $request->attributes->get('room_user');
        $query = $roomUser->orders()->with(['items.toppings', 'campaign'])->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        return response()->json(['data' => $query->paginate(20)]);
    }

    public function store(StoreOrderRequest $request, Room $room, Campaign $campaign, CreateOrderAction $action): JsonResponse
    {
        try {
            $order = $action->execute($campaign, $request->attributes->get('room_user'), $request->validated());
        } catch (QueryException $exception) {
            $message = $exception->getMessage();
            if (str_contains($message, 'orders_one_active_per_user_campaign')
                || str_contains($message, 'UNIQUE constraint failed: orders.campaign_id, orders.room_user_id')) {
                $roomUser = $request->attributes->get('room_user');
                $activeOrder = $roomUser?->orders()
                    ->where('campaign_id', $campaign->id)
                    ->whereIn('status', ['submitted', 'confirmed', 'ordering', 'ordered', 'delivering'])
                    ->latest()
                    ->first();
                $room = $request->attributes->get('room');

                return response()->json([
                    'message' => 'Bạn đã có một đơn đang hoạt động cho campaign này.',
                    'code' => 'active_order_exists',
                    'order_id' => $activeOrder?->id,
                    'order_status' => $activeOrder?->status?->value,
                    'order_url' => $activeOrder && $room ? route('user.orders.page', [$room, $activeOrder]) : null,
                ], 422);
            }
            throw $exception;
        }

        return response()->json(['data' => $order], 201);
    }

    public function show(Request $request, Room $room, Order $order): JsonResponse
    {
        $roomUser = $request->attributes->get('room_user');
        abort_unless($order->room_id === $room->id && $order->room_user_id === $roomUser->id, 404);

        return response()->json(['data' => $order->load(['items.toppings', 'campaign', 'room'])]);
    }

    public function payment(Request $request, Room $room, Order $order): JsonResponse
    {
        $roomUser = $request->attributes->get('room_user');
        abort_unless($order->room_id === $room->id && $order->room_user_id === $roomUser->id, 404);
        $account = $order->campaign()->with('paymentAccount')->first()?->paymentAccount;
        abort_unless($account && $account->status === 'active', 404);
        $amount = (int) $order->final_amount;
        $content = 'DRINKFLOW-'.$order->id;

        return response()->json(['data' => [
            'bank_code' => $account->bank_code,
            'bank_name' => $account->bank_name,
            'account_number' => $account->account_number,
            'account_name' => $account->account_name,
            'amount' => $amount,
            'transfer_content' => $content,
            'qr_url' => sprintf('https://img.vietqr.io/image/%s-%s-compact2.png?amount=%d&addInfo=%s', rawurlencode($account->bank_code), rawurlencode($account->account_number), $amount, rawurlencode($content)),
        ]]);
    }
}
