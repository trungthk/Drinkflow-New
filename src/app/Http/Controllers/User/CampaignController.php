<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Actions\Campaign\DeclineCampaignAction;
use App\Actions\Campaign\RejoinCampaignAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCampaignCartRequest;
use App\Models\Campaign;
use App\Services\Campaign\UserRoomCampaignService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CampaignController extends Controller
{
    /**
     * Add a configured campaign item to the current user's session cart.
     *
     * @param Request $request Current HTTP request.
     * @param \App\Models\Room $room Current room.
     * @param Campaign $campaign Target campaign.
     * @return JsonResponse Session cart payload.
     */
    public function addToCart(StoreCampaignCartRequest $request, \App\Models\Room $room, Campaign $campaign): JsonResponse
    {
        abort_unless($campaign->room_id === $room->id, 404);

        if (! $campaign->isOrderable()) {
            throw ValidationException::withMessages([
                'campaign' => __('room.campaign.ordering_closed'),
            ]);
        }

        $data = $request->validated();

        $item = $campaign->items()->with(['sizes', 'toppings'])->whereKey($data['item_id'])->where('status', 'active')->firstOrFail();
        $size = ! empty($data['size_id']) ? $item->sizes->firstWhere('id', (int) $data['size_id']) : null;
        abort_if(! empty($data['size_id']) && ! $size, 422, __('admin.invalid_size'));
        $toppings = collect($data['topping_ids'] ?? [])->map(fn (int $id) => $item->toppings->firstWhere('id', $id))->filter();
        abort_if($toppings->count() !== count($data['topping_ids'] ?? []), 422, __('admin.invalid_topping'));

        $cartKey = $this->cartKey($room->id, $campaign->id);
        $cart = session()->get($cartKey, []);
        $cart[] = [
            'item_id' => (int) $item->id,
            'item_name' => $item->name,
            'size_id' => $size?->id,
            'size_name' => $size?->name,
            'topping_ids' => $toppings->pluck('id')->values()->all(),
            'topping_names' => $toppings->pluck('name')->values()->all(),
            'quantity' => (int) $data['quantity'],
            'note' => $data['note'] ?? null,
            'unit_price' => (int) $item->base_price + (int) ($size?->price_delta ?? 0) + (int) $toppings->sum('price'),
        ];
        session()->put($cartKey, $cart);

        return response()->json(['data' => $cart]);
    }

    /**
     * Build the session key used by one room campaign cart.
     *
     * @param int $roomId Room identifier.
     * @param int $campaignId Campaign identifier.
     * @return string Session key.
     */
    private function cartKey(int $roomId, int $campaignId): string
    {
        return "room_campaign_cart_{$roomId}_{$campaignId}";
    }

    /**
     * Remove one item from the current room campaign session cart.
     *
     * @param Request $request Current HTTP request.
     * @param \App\Models\Room $room Current room.
     * @param Campaign $campaign Target campaign.
     * @param int $index Zero-based cart item index.
     * @return JsonResponse Updated cart payload.
     */
    public function removeFromCart(Request $request, \App\Models\Room $room, Campaign $campaign, int $index): JsonResponse
    {
        abort_unless($campaign->room_id === $room->id, 404);
        $cartKey = $this->cartKey($room->id, $campaign->id);
        $cart = session()->get($cartKey, []);
        abort_if(! array_key_exists($index, $cart), 404);
        unset($cart[$index]);
        $cart = array_values($cart);
        session()->put($cartKey, $cart);

        return response()->json(['data' => $cart]);
    }

    /**
     * Clear the current room campaign session cart.
     *
     * @param Request $request Current HTTP request.
     * @param \App\Models\Room $room Current room.
     * @param Campaign $campaign Target campaign.
     * @return JsonResponse Empty cart payload.
     */
    public function clearCart(Request $request, \App\Models\Room $room, Campaign $campaign): JsonResponse
    {
        abort_unless($campaign->room_id === $room->id, 404);
        session()->forget($this->cartKey($room->id, $campaign->id));

        return response()->json(['data' => []]);
    }

    /**
     * Hiển thị danh sách chiến dịch hoặc menu món đang diễn ra trong phòng.
     *
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request
     * @param  \App\Services\Campaign\UserRoomCampaignService  $service  Service xử lý chiến dịch phòng
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Contracts\View\View  Phản hồi JSON hoặc Giao diện View
     */
    public function index(Request $request, UserRoomCampaignService $service): JsonResponse|View
    {
        $room = $request->attributes->get('room');
        $roomUser = $request->attributes->get('room_user');
        $user = $request->attributes->get('global_user') ?? $request->user('web');

        if ($request->expectsJson() || $request->filled('q')) {
            $campaigns = $service->searchCampaigns($room, $request);
            return response()->json(['data' => $campaigns]);
        }

        $data = $service->getCampaignViewData($room, $roomUser, $user);

        return view('user.campaign', $data);
    }

    /**
     * Xem thông tin chi tiết một chiến dịch cụ thể trong phòng.
     *
     * @param  \App\Models\Campaign  $campaign  Chiến dịch cần xem
     * @param  \Illuminate\Http\Request  $request  Đối tượng HTTP Request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(Campaign $campaign, Request $request): JsonResponse|View|RedirectResponse
    {
        $room = $request->attributes->get('room');
        abort_unless($campaign->room_id === $room->id && in_array($campaign->status?->value, ['active', 'scheduled'], true), 404);

        if ($request->expectsJson()) {
            return response()->json(['data' => $campaign->load(['items' => fn ($q) => $q->where('status', 'active')->with(['sizes', 'toppings']), 'room'])]);
        }

        return redirect()->route('user.campaigns.index', $room->slug);
    }

    /**
     * Record that the authenticated room member will not participate in a campaign.
     *
     * @param Request $request Current HTTP request.
     * @param Room $room Current room.
     * @param Campaign $campaign Campaign being declined.
     * @param DeclineCampaignAction $action Participation action.
     * @return JsonResponse Declined participation payload.
     */
    public function decline(Request $request, \App\Models\Room $room, Campaign $campaign, DeclineCampaignAction $action): JsonResponse
    {
        /** @var \App\Models\RoomUser $roomUser */
        $roomUser = $request->attributes->get('room_user');
        abort_unless($campaign->room_id === $room->id, 404);
        $this->ensureCampaignIsOrderable($campaign);

        return response()->json(['data' => $action->execute($campaign, $roomUser)]);
    }

    /**
     * Restore the authenticated room member's participation in an active campaign.
     *
     * @param Request $request Current HTTP request.
     * @param \App\Models\Room $room Current room.
     * @param Campaign $campaign Campaign being rejoined.
     * @param RejoinCampaignAction $action Participation action.
     * @return JsonResponse Rejoin response.
     */
    public function rejoin(Request $request, \App\Models\Room $room, Campaign $campaign, RejoinCampaignAction $action): JsonResponse
    {
        /** @var \App\Models\RoomUser $roomUser */
        $roomUser = $request->attributes->get('room_user');
        abort_unless($campaign->room_id === $room->id, 404);
        $this->ensureCampaignIsOrderable($campaign);

        $action->execute($campaign, $roomUser);

        return response()->json(['data' => ['status' => 'pending']]);
    }

    /**
     * Ensure that campaign participation changes are only available while ordering is open.
     *
     * @param Campaign $campaign Campaign being updated.
     * @return void
     * @throws ValidationException When the campaign is not currently orderable.
     */
    private function ensureCampaignIsOrderable(Campaign $campaign): void
    {
        if (! $campaign->isOrderable()) {
            throw ValidationException::withMessages([
                'campaign' => __('room.campaign.ordering_closed'),
            ]);
        }
    }
}
