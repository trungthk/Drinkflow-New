<?php

namespace App\Http\Controllers\User;

use App\Actions\Order\CreateOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Campaign;
use App\Models\Order;
use App\Models\Room;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Handle the index operation.
     * @param Request $request Parameter value.
     * @return JsonResponse|View Result of the operation.
     */
    public function index(Request $request): JsonResponse|View
    {
        $room = $request->attributes->get('room');
        $roomUser = $request->attributes->get('room_user');
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $query = $roomUser->orders()->where('room_id', $room->id)->with(['items.toppings', 'campaign.paymentAccount'])->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        $orders = $query->paginate(20);

        if ($request->expectsJson()) {
            return response()->json(['data' => $orders]);
        }

        $activeCampaign = $room->campaigns()->where('status', 'active')->first();
        $userRooms = $user ? $user->rooms()->where('rooms.status', 'active')->get() : collect();
        $unreadCount = DB::table('user_notifications')->where('global_user_id', $user->id)->where('is_read', false)->count();

        return view('user.orders', [
            'room' => $room,
            'roomUser' => $roomUser,
            'user' => $user,
            'orders' => $orders,
            'activeCampaign' => $activeCampaign ? [
                'name' => $activeCampaign->name,
                'time_remaining' => $activeCampaign->deadline ? ($activeCampaign->deadline->isFuture() ? $activeCampaign->deadline->diffForHumans(['parts' => 2, 'short' => true]) : '00:00') : '14:22',
            ] : null,
            'userRooms' => $userRooms,
            'unreadNotificationsCount' => $unreadCount,
        ]);
    }

    /**
     * Handle the store operation.
     * @param StoreOrderRequest $request Parameter value.
     * @param Room $room Parameter value.
     * @param Campaign $campaign Parameter value.
     * @param CreateOrderAction $action Parameter value.
     * @return JsonResponse|RedirectResponse Result of the operation.
     */
    public function store(StoreOrderRequest $request, Room $room, Campaign $campaign, CreateOrderAction $action): JsonResponse|RedirectResponse
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

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => __('global.orders.active_order_exists'),
                        'code' => 'active_order_exists',
                        'order_id' => $activeOrder?->id,
                        'order_status' => $activeOrder?->status?->value,
                        'order_url' => $activeOrder && $room ? route('user.orders.page', [$room, $activeOrder]) : null,
                    ], 422);
                }

                return redirect()->route('user.orders.index', $room->slug)->with('error', __('global.orders.active_order_exists'));
            }
            throw $exception;
        }

        if ($request->expectsJson()) {
            return response()->json(['data' => $order], 201);
        }

        return redirect()->route('user.orders.index', $room->slug)->with('status', __('room.campaign.order_success'));
    }

    /**
     * Handle the show operation.
     * @param Request $request Parameter value.
     * @param Room $room Parameter value.
     * @param Order $order Parameter value.
     * @return JsonResponse|View Result of the operation.
     */
    public function show(Request $request, Room $room, Order $order): JsonResponse|View
    {
        $roomUser = $request->attributes->get('room_user');
        abort_unless($order->room_id === $room->id && $order->room_user_id === $roomUser->id, 404);

        if ($request->expectsJson()) {
            return response()->json(['data' => $order->load(['items.toppings', 'campaign', 'room'])]);
        }

        return redirect()->route('user.orders.index', $room->slug);
    }

    /**
     * Handle the payment operation.
     * @param Request $request Parameter value.
     * @param Room $room Parameter value.
     * @param Order $order Parameter value.
     * @return JsonResponse Result of the operation.
     */
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
