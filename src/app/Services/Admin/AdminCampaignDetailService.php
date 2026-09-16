<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\OrderStatus;
use App\Enums\RoomUserStatus;
use App\Models\Campaign;
use App\Models\CampaignParticipant;
use App\Models\Room;

class AdminCampaignDetailService
{
    /**
     * Build aggregated financial, participation and item details for campaign view.
     *
     * @param Room $room Room entity.
     * @param Campaign $campaign Campaign entity.
     * @return array<string, mixed> Aggregated view payload.
     */
    public function getCampaignViewData(Room $room, Campaign $campaign): array
    {
        $campaign->load([
            'items.sizes',
            'items.toppings',
            'paymentAccount',
            'orders.roomUser.globalUser',
            'orders.items',
            'debts.roomUser.globalUser',
        ]);

        $orders = $campaign->orders->whereNotIn('status', [OrderStatus::Cancelled->value]);
        $totalUsersCount = $room->roomUsers()->where('status', RoomUserStatus::Active)->count() ?: 1;
        $orderedUsersCount = $orders->pluck('room_user_id')->unique()->count();
        $declinedUsersCount = CampaignParticipant::query()
            ->where('campaign_id', $campaign->id)
            ->where('status', CampaignParticipant::STATUS_DECLINED)
            ->whereHas('roomUser', fn ($query) => $query->where('room_id', $room->id)->where('status', RoomUserStatus::Active->value))
            ->count();
        $pendingUsersCount = max(0, $totalUsersCount - $orderedUsersCount - $declinedUsersCount);

        $aggregatedItems = collect();
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $key = $item->item_name . '|' . ($item->size_name ?? '');
                if (! $aggregatedItems->has($key)) {
                    $aggregatedItems->put($key, [
                        'name' => $item->item_name,
                        'size' => $item->size_name,
                        'unit_price' => $item->unit_price,
                        'quantity' => 0,
                        'total_amount' => 0,
                        'notes' => collect(),
                    ]);
                }
                $curr = $aggregatedItems->get($key);
                $curr['quantity'] += $item->quantity;
                $curr['total_amount'] += $item->line_subtotal;
                if (! empty($item->note)) {
                    $curr['notes']->push($item->note);
                }
                $aggregatedItems->put($key, $curr);
            }
        }

        $grossSubtotal = (int) $orders->sum('subtotal');
        $sponsorSubsidy = (int) $orders->sum('sponsor_amount');
        $netPayables = (int) $orders->sum('final_amount');
        $paidViaQr = (int) $campaign->debts->sum('paid_amount');
        $memberDebt = (int) $campaign->debts->whereIn('status', ['unpaid', 'partial'])->sum('remaining_amount');

        return [
            'room' => $room,
            'campaign' => $campaign,
            'orders' => $orders,
            'aggregatedItems' => $aggregatedItems->values(),
            'totalUsersCount' => $totalUsersCount,
            'orderedUsersCount' => $orderedUsersCount,
            'declinedUsersCount' => $declinedUsersCount,
            'pendingUsersCount' => $pendingUsersCount,
            'grossSubtotal' => $grossSubtotal,
            'sponsorSubsidy' => $sponsorSubsidy,
            'netPayables' => $netPayables,
            'paidViaQr' => $paidViaQr,
            'memberDebt' => $memberDebt,
        ];
    }
}
