<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Actions\Order\CreateOrderAction;
use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentAccountStatus;
use App\Enums\PaymentStatus;
use App\Enums\RoomStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Campaign;
use App\Models\Debt;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\PaymentAccount;
use App\Models\Room;
use App\Models\RoomUser;
use App\Services\Payment\VietQrService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a paginated list of user's orders in the room, or JSON response for API requests.
     *
     * @param Request $request Current HTTP request.
     * @return JsonResponse|View View response or JSON payload.
     */
    public function index(Request $request): JsonResponse|View
    {
        /** @var Room $room */
        $room = $request->attributes->get('room');
        /** @var RoomUser $roomUser */
        $roomUser = $request->attributes->get('room_user');
        /** @var GlobalUser|null $user */
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        $query = $roomUser->orders()
            ->where('room_id', $room->id)
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->with(['items.toppings', 'campaign.paymentAccount'])
            ->latest();
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

        $activeCampaign = $room->campaigns()->where('status', CampaignStatus::Active->value)->first();
        $userRooms = $user ? $user->rooms()->where('rooms.status', RoomStatus::Active->value)->get() : collect();
        $unreadCount = $user ? DB::table('user_notifications')->where('global_user_id', $user->id)->whereNull('read_at')->count() : 0;
        $activeOrder = $orders->first();
        $paymentConfirmationDetails = null;
        if ($activeOrder instanceof Order) {
            $debt = Debt::query()
                ->where('campaign_id', $activeOrder->campaign_id)
                ->where('room_user_id', $activeOrder->room_user_id)
                ->with('payments.createdByAdmin')
                ->first();
            $approvalPayment = $activeOrder->payment_status === PaymentStatus::Paid
                ? $debt?->payments->sortByDesc('paid_at')->first()
                : null;

            $paymentConfirmationDetails = [
                'requestedAt' => $debt?->payment_requested_at?->format('d/m/Y H:i'),
                'content' => $debt?->note ?: ($activeOrder->code ?: ('DF'.$activeOrder->id.' '.$roomUser->room_user_code)),
                'approvedBy' => $approvalPayment?->createdByAdmin?->name,
                'approvedAt' => $approvalPayment?->paid_at?->format('d/m/Y H:i'),
            ];
        }

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
            'paymentConfirmationDetails' => $paymentConfirmationDetails,
        ]);
    }

    /**
     * Store a newly created order for the specified campaign.
     *
     * @param StoreOrderRequest $request Validated order store request.
     * @param Room $room Current room model.
     * @param Campaign $campaign Target campaign model.
     * @param CreateOrderAction $action Domain action to create order.
     * @return JsonResponse|RedirectResponse Redirect to orders list or JSON response.
     */
    public function store(StoreOrderRequest $request, Room $room, Campaign $campaign, CreateOrderAction $action): JsonResponse|RedirectResponse
    {
        try {
            /** @var RoomUser $roomUser */
            $roomUser = $request->attributes->get('room_user');
            $order = $action->execute($campaign, $roomUser, $request->validated());
        } catch (QueryException $exception) {
            $message = $exception->getMessage();
            if (str_contains($message, 'orders_one_active_per_user_campaign')
                || str_contains($message, 'UNIQUE constraint failed: orders.campaign_id, orders.room_user_id')) {
                /** @var RoomUser|null $roomUser */
                $roomUser = $request->attributes->get('room_user');
                $activeOrder = $roomUser?->orders()
                    ->where('campaign_id', $campaign->id)
                    ->whereIn('status', [
                        OrderStatus::Submitted->value,
                        OrderStatus::Confirmed->value,
                        OrderStatus::Ordering->value,
                        OrderStatus::Ordered->value,
                        OrderStatus::Delivering->value,
                    ])
                    ->latest()
                    ->first();
                /** @var Room|null $roomAttr */
                $roomAttr = $request->attributes->get('room');

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => __('global.orders.active_order_exists'),
                        'code' => 'active_order_exists',
                        'order_id' => $activeOrder?->id,
                        'order_status' => $activeOrder?->status?->value,
                        'order_url' => $activeOrder && $roomAttr ? route('user.orders.page', [$roomAttr, $activeOrder]) : null,
                    ], 422);
                }

                return redirect()->route('user.orders.index', $room->slug)->with('error', __('global.orders.active_order_exists'));
            }
            throw $exception;
        }

        if ($request->expectsJson()) {
            $request->session()->forget("room_campaign_cart_{$room->id}_{$campaign->id}");
            return response()->json(['data' => $order], 201);
        }

        $request->session()->forget("room_campaign_cart_{$room->id}_{$campaign->id}");
        return redirect()->route('user.orders.index', $room->slug)->with('status', __('room.campaign.order_success'));
    }

    /**
     * Display the specified order details or redirect to order page.
     *
     * @param Request $request Current HTTP request.
     * @param Room $room Current room model.
     * @param Order $order Target order model.
     * @return JsonResponse|View|RedirectResponse JSON payload or redirect response.
     */
    public function show(Request $request, Room $room, Order $order): JsonResponse|View|RedirectResponse
    {
        /** @var RoomUser $roomUser */
        $roomUser = $request->attributes->get('room_user');
        abort_unless($order->room_id === $room->id && $order->room_user_id === $roomUser->id, 404);

        if ($request->expectsJson()) {
            return response()->json(['data' => $order->load(['items.toppings', 'campaign', 'room'])]);
        }

        return redirect()->route('user.orders.index', $room->slug);
    }

    /**
     * Retrieve payment details and QR code for an order.
     *
     * @param Request $request Current HTTP request.
     * @param Room $room Current room model.
     * @param Order $order Target order model.
     * @return JsonResponse JSON payload containing bank info and QR payment link.
     */
    public function payment(Request $request, Room $room, Order $order): JsonResponse
    {
        /** @var RoomUser $roomUser */
        $roomUser = $request->attributes->get('room_user');
        abort_unless($order->room_id === $room->id && $order->room_user_id === $roomUser->id, 404);

        /** @var PaymentAccount|null $account */
        $account = $order->campaign?->paymentAccount
            ?? $room->paymentAccounts()->where('is_default', true)->first()
            ?? $room->paymentAccounts()->first();
        abort_unless($account && $account->status === PaymentAccountStatus::Active, 404);

        $amount  = (int) $order->final_amount;
        $content = $order->code ?: ('DRINKFLOW-'.$order->id);

        /** @var VietQrService $vietQr */
        $vietQr = app(VietQrService::class);

        return response()->json(['data' => [
            'bank_code'        => $account->bank_code,
            'bank_name'        => $account->bank_name,
            'account_number'   => $account->account_number,
            'account_name'     => $account->account_name,
            'amount'           => $amount,
            'transfer_content' => $content,
            'qr_url'           => $vietQr->imageUrl($account, $amount, $content),
        ]]);
    }

    /**
     * Submit payment confirmation for an order to await admin approval.
     *
     * @param Request $request Incoming request.
     * @param Room $room Target room model.
     * @param Order $order Target order model.
     * @param \App\Actions\Order\ConfirmOrderPaymentAction $action Domain action to confirm payment.
     * @return JsonResponse|RedirectResponse Success response.
     */
    public function confirmPayment(Request $request, Room $room, Order $order, \App\Actions\Order\ConfirmOrderPaymentAction $action): JsonResponse|RedirectResponse
    {
        /** @var RoomUser $roomUser */
        $roomUser = $request->attributes->get('room_user');
        abort_unless($order->room_id === $room->id && $order->room_user_id === $roomUser->id, 404);

        $action->execute($order, $roomUser);
        $debt = Debt::query()
            ->where('campaign_id', $order->campaign_id)
            ->where('room_user_id', $roomUser->id)
            ->first();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('room.orders.payment_submitted_success'),
                'payment_status' => 'pending',
                'payment_confirmation' => [
                    'requestedAt' => $debt?->payment_requested_at?->format('d/m/Y H:i'),
                    'content' => $debt?->note,
                    'approvedBy' => null,
                    'approvedAt' => null,
                ],
            ]);
        }

        return redirect()->route('user.orders.index', $room->slug)->with('status', __('room.orders.payment_submitted_success'));
    }
}
