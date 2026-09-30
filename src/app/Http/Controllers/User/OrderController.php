<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Actions\Order\CreateOrderAction;
use App\Actions\Order\CreateProxyOrdersAction;
use App\Enums\OrderStatus;
use App\Exceptions\ActiveOrderExistsException;
use App\Enums\PaymentAccountStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Campaign;
use App\Models\Debt;
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

        $query = Order::query()
            ->where('room_id', $room->id)
            ->where(static function (\Illuminate\Database\Eloquent\Builder $orderQuery) use ($roomUser): void {
                $orderQuery->where('room_user_id', $roomUser->id)
                    ->orWhereHas('parent', static function (\Illuminate\Database\Eloquent\Builder $parentQuery) use ($roomUser): void {
                        $parentQuery->where('room_user_id', $roomUser->id);
                    });
            })
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->with([
                'items.toppings', 'items.campaignItem',
                'children.roomUser.globalUser', 'children.items.toppings', 'children.items.campaignItem',
                // Only the campaign total is needed (to share delivery/discount): never load other members' orders.
                'campaign' => static fn ($campaignQuery) => $campaignQuery
                    ->with('paymentAccount')
                    ->withSum(['orders as active_orders_subtotal' => static fn ($orders) => $orders
                        ->where('status', '!=', OrderStatus::Cancelled->value)
                        ->whereNull('cancelled_at')], 'subtotal'),
            ])
            ->latest();

        $orders = $query->paginate(20);

        if ($request->expectsJson()) {
            // Explicit field list: the member's own orders and those placed for others under them.
            return \App\Http\Resources\Member\OrderResource::collection($orders)->response();
        }

        $activeOrder = $orders->firstWhere('room_user_id', $roomUser->id) ?? $orders->first();
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
                'content' => $debt?->note ?: $activeOrder->code,
                'approvedBy' => $approvalPayment?->createdByAdmin?->name,
                'approvedAt' => $approvalPayment?->paid_at?->format('d/m/Y H:i'),
            ];
        }

        return view('user.orders', [
            'orders' => $orders,
            'activeOrder' => $activeOrder,
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
    public function store(StoreOrderRequest $request, Room $room, Campaign $campaign, CreateOrderAction $action, CreateProxyOrdersAction $proxyAction): JsonResponse|RedirectResponse
    {
        try {
            /** @var RoomUser $roomUser */
            $roomUser = $request->attributes->get('room_user');
            $data = $request->validated();
            $hasProxyItems = collect($data['items'] ?? [])->contains(
                static fn(array $item): bool => !empty($item['proxy_user_code'])
            );
            $order = $hasProxyItems
                ? $proxyAction->execute($campaign, $roomUser, $data)
                : $action->execute($campaign, $roomUser, $data);
        } catch (ActiveOrderExistsException) {
            return $this->activeOrderExistsResponse($request, $room, $campaign);
        } catch (QueryException $exception) {
            // Concurrent submit: the unique index rejected the second active order.
            if (ActiveOrderExistsException::isViolation($exception)) {
                return $this->activeOrderExistsResponse($request, $room, $campaign);
            }
            throw $exception;
        }

        if ($request->expectsJson()) {
            $request->session()->forget("room_campaign_cart_{$room->id}_{$campaign->id}");
            return (new \App\Http\Resources\Member\OrderResource($order))->response()->setStatusCode(201);
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
        $canViewOrder = $order->room_user_id === $roomUser->id
            || $order->parent()->where('room_user_id', $roomUser->id)->exists();
        abort_unless($order->room_id === $room->id && $canViewOrder, 404);

        if ($request->expectsJson()) {
            return (new \App\Http\Resources\Member\OrderResource($order->load(['items.toppings', 'campaign', 'room'])))->response();
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

        /** @var VietQrService $vietQr */
        $vietQr = app(VietQrService::class);

        $amount = (int) $order->final_amount;
        $content = $vietQr->transferContent((string) $order->code, $roomUser->payerName());

        return response()->json([
            'data' => [
                'bank_code' => $account->bank_code,
                'bank_name' => $account->bank_name,
                'account_number' => $account->account_number,
                'account_name' => $account->account_name,
                'amount' => $amount,
                'transfer_content' => $content,
                'payload' => $vietQr->generate($account, $amount, $content),
            ]
        ]);
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
                    'content' => $debt?->payment_content ?: $debt?->note,
                    'approvedBy' => null,
                    'approvedAt' => null,
                ],
            ]);
        }

        return redirect()->route('user.orders.index', $room->slug)->with('status', __('room.orders.payment_submitted_success'));
    }

    /**
     * Response when the member already has an active order in the campaign.
     *
     * @param Request $request Incoming request (room_user / room attributes set by middleware).
     * @param Room $room Current room.
     * @param Campaign $campaign Target campaign.
     * @return JsonResponse|RedirectResponse 422 JSON with the existing order, or a redirect with the error.
     */
    private function activeOrderExistsResponse(Request $request, Room $room, Campaign $campaign): JsonResponse|RedirectResponse
    {
        /** @var RoomUser|null $roomUser */
        $roomUser = $request->attributes->get('room_user');
        $activeOrder = $roomUser?->orders()
            ->where('campaign_id', $campaign->id)
            ->whereIn('status', OrderStatus::activeValues())
            ->latest()
            ->first();
        /** @var Room|null $roomAttr */
        $roomAttr = $request->attributes->get('room');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('global.orders.active_order_exists'),
                'code' => ActiveOrderExistsException::CODE,
                'order_id' => $activeOrder?->id,
                'order_status' => $activeOrder?->status?->value,
                'order_url' => $activeOrder && $roomAttr ? route('user.orders.page', [$roomAttr, $activeOrder]) : null,
            ], 422);
        }

        return redirect()->route('user.orders.index', $room->slug)->with('error', __('global.orders.active_order_exists'));
    }
}
