<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\CampaignItemStatus;
use App\Enums\GlobalUserStatus;
use App\Enums\OrderStatus;
use App\Enums\RoomUserStatus;
use App\Models\Campaign;
use App\Models\CampaignItem;
use App\Models\Room;
use App\Models\RoomUser;

class AdminOrderOnBehalfService
{
    /**
     * Build the data behind the admin "order on behalf" modal: the campaign's orderable menu and
     * the active members who have no active order in it yet.
     *
     * @param Room $room Current room.
     * @param Campaign|null $campaign Campaign shown on the orders page.
     * @return array{campaign_id: int, items: array<int, array<string, mixed>>, members: array<int, array<string, mixed>>}|null
     *         Modal data, or null when the campaign does not accept orders.
     */
    public function formData(Room $room, ?Campaign $campaign): ?array
    {
        if ($campaign === null || $campaign->room_id !== $room->id || ! $campaign->isOpenForOrders()) {
            return null;
        }

        $activeStatus = CampaignItemStatus::Active->value;
        $items = $campaign->items()
            ->where('status', $activeStatus)
            ->with([
                'sizes' => static fn ($query) => $query->where('status', $activeStatus)->orderBy('sort_order'),
                'toppings' => static fn ($query) => $query->where('status', $activeStatus)->orderBy('sort_order'),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(static fn (CampaignItem $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category,
                'price' => (int) $item->base_price,
                'sizes' => $item->sizes->map(static fn ($size): array => [
                    'id' => $size->id,
                    'name' => $size->name,
                    'price_delta' => (int) $size->price_delta,
                ])->values()->all(),
                'toppings' => $item->toppings->map(static fn ($topping): array => [
                    'id' => $topping->id,
                    'name' => $topping->name,
                    'price' => (int) $topping->price,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        $members = $room->roomUsers()
            ->where('status', RoomUserStatus::Active->value)
            ->whereHas('globalUser', static fn ($query) => $query->where('status', GlobalUserStatus::Active->value))
            ->whereDoesntHave('orders', static fn ($query) => $query
                ->where('campaign_id', $campaign->id)
                ->where('status', '!=', OrderStatus::Cancelled->value))
            ->with('globalUser:id,name,email')
            ->get()
            ->sortBy(static fn (RoomUser $member): string => mb_strtolower($member->payerName()))
            ->map(static fn (RoomUser $member): array => [
                'id' => $member->id,
                'name' => $member->payerName(),
                'email' => $member->globalUser?->email,
            ])
            ->values()
            ->all();

        return [
            'campaign_id' => $campaign->id,
            'items' => $items,
            'members' => $members,
        ];
    }
}
