<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\CampaignStatus;
use App\Enums\DebtStatus;
use App\Enums\OrderStatus;
use App\Enums\RoomUserStatus;
use App\Models\Campaign;
use App\Models\CampaignParticipant;
use App\Models\Order;
use App\Models\OrderItem;
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
            'orders.parent.roomUser.globalUser',
            'orders.items.toppings',
            'debts.roomUser.globalUser',
        ]);

        $orders = $campaign->orders
            ->filter(static fn (Order $order): bool => $order->status !== OrderStatus::Cancelled && $order->cancelled_at === null)
            ->values();
        $allActiveRoomUsers = $room->roomUsers()
            ->where('status', RoomUserStatus::Active)
            ->with('globalUser')
            ->get();
        $totalUsersCount = $allActiveRoomUsers->count() ?: 1;
        $orderedUserIds = $orders->pluck('room_user_id')->unique()->filter()->all();
        $orderedUsersCount = count($orderedUserIds);

        $declinedParticipantRoomUserIds = CampaignParticipant::query()
            ->where('campaign_id', $campaign->id)
            ->where('status', CampaignParticipant::STATUS_DECLINED)
            ->whereIn('room_user_id', $allActiveRoomUsers->pluck('id'))
            ->pluck('room_user_id')
            ->all();

        $declinedUsers = $allActiveRoomUsers->whereIn('id', $declinedParticipantRoomUserIds)->values();
        $declinedUsersCount = $declinedUsers->count();

        $orderedAndDeclinedIds = array_unique(array_merge($orderedUserIds, $declinedParticipantRoomUserIds));
        $unresponsiveUsers = $allActiveRoomUsers->whereNotIn('id', $orderedAndDeclinedIds)->values();
        $pendingUsersCount = $unresponsiveUsers->count();

        $aggregatedItems = collect();
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $toppingLabels = $item->toppings
                    ->map(static fn ($topping): string => (($topping->quantity > 1 ? $topping->quantity.'x ' : '').trim((string) $topping->topping_name)))
                    ->filter(static fn (string $label): bool => trim($label) !== '')
                    ->sort()
                    ->values();
                // Different toppings / ice / sugar are different drinks for the store, so they get their own line.
                $key = implode('|', [$item->item_name, $item->size_name ?? '', $toppingLabels->join(';'), $item->ice_percent ?? '', $item->sugar_percent ?? '']);
                if (! $aggregatedItems->has($key)) {
                    $aggregatedItems->put($key, [
                        'name' => $item->item_name,
                        'size' => $item->size_name,
                        'unit_price' => $item->unit_price,
                        'quantity' => 0,
                        'member_quantities' => collect(),
                        'total_amount' => 0,
                        'notes' => collect(),
                        'toppings' => $toppingLabels,
                        'ice_percent' => $item->ice_percent,
                        'sugar_percent' => $item->sugar_percent,
                    ]);
                }
                $curr = $aggregatedItems->get($key);
                $curr['quantity'] += $item->quantity;
                $memberKey = $order->room_user_id ?? 'order-'.$order->id;
                $memberQuantity = $curr['member_quantities']->get($memberKey, [
                    'name' => $order->roomUser?->display_name ?? __('admin.member'),
                    'quantity' => 0,
                ]);
                $memberQuantity['quantity'] += $item->quantity;
                $curr['member_quantities']->put($memberKey, $memberQuantity);
                $curr['total_amount'] += $item->line_subtotal;
                if (! empty($item->note)) {
                    $curr['notes']->push($item->note);
                }
                $aggregatedItems->put($key, $curr);
            }
        }

        $grossSubtotal = (int) $orders->sum('subtotal');
        $grossTotal = max(0, $grossSubtotal + (int) ($campaign->delivery_fee ?? 0) - (int) ($campaign->discount ?? 0));
        $sponsorSubsidy = $campaign->sponsor_type === Campaign::SPONSOR_TYPE_FULL
            ? $grossTotal
            : (int) $orders->sum('sponsor_amount');
        $netPayables = (int) $orders->sum('final_amount');
        $paidViaQr = (int) $campaign->debts->sum('paid_amount');
        $memberDebt = (int) $campaign->debts->whereIn('status', [DebtStatus::Unpaid->value, DebtStatus::Partial->value])->sum('remaining_amount');

        $sponsorAllocationsData = collect($campaign->sponsor_allocations ?? []);
        $sponsorUserIds = $sponsorAllocationsData->pluck('room_user_id')->filter()->map(fn($id) => (int) $id);
        $sponsorRoomUsers = $sponsorUserIds->isNotEmpty()
            ? $room->roomUsers()->with('globalUser')->whereIn('id', $sponsorUserIds)->get()->keyBy('id')
            : collect();

        $sponsorsList = $sponsorAllocationsData->map(function ($alloc) use ($sponsorRoomUsers, $sponsorSubsidy): array {
            $user = $sponsorRoomUsers->get((int) ($alloc['room_user_id'] ?? 0));
            $name = $user?->display_name ?? $user?->globalUser?->name ?? __('admin.sponsor_info');
            $percentage = (float) ($alloc['percentage'] ?? 0);
            $amount = (int) round(($sponsorSubsidy * $percentage) / 100);
            return [
                'name' => $name,
                'percentage' => $percentage,
                'amount' => $amount,
                'avatar' => $user?->globalUser?->avatar_url ?? null,
            ];
        })->values();

        // Match the sponsor debt allocation: assign rounding remainder to the first sponsor.
        if ($campaign->sponsor_type === Campaign::SPONSOR_TYPE_FULL && $sponsorsList->isNotEmpty()) {
            $firstSponsor = $sponsorsList->first();
            $firstSponsor['amount'] = max(0, $firstSponsor['amount'] + $sponsorSubsidy - (int) $sponsorsList->sum('amount'));
            $sponsorsList->put(0, $firstSponsor);
        }

        if ($sponsorsList->isEmpty() && !empty($campaign->sponsor_name)) {
            $sponsorsList->push([
                'name' => $campaign->sponsor_name,
                'percentage' => 100,
                'amount' => $sponsorSubsidy,
                'avatar' => null,
            ]);
        }

        $departmentGroups = collect();
        foreach ($orders as $order) {
            $user = $order->roomUser;
            $globalUser = $user?->globalUser;
            $deskLocation = self::normalizeDeskLocation($globalUser?->desk_location);
            $department = $deskLocation !== '' ? mb_strtolower($deskLocation) : '';

            if (! $departmentGroups->has($department)) {
                $departmentGroups->put($department, [
                    'department' => $deskLocation !== '' ? $deskLocation : __('admin.unassigned_department'),
                    'is_unassigned' => $deskLocation === '',
                    'orders' => collect(),
                    'members' => collect(),
                    'items' => collect(),
                    'total_quantity' => 0,
                    'total_amount' => 0,
                ]);
            }

            $dept = $departmentGroups->get($department);
            $dept['orders']->push($order);
            if ($user && ! $dept['members']->contains('id', $user->id)) {
                $dept['members']->push($user);
            }

            foreach ($order->items as $item) {
                $toppingLabels = $item->toppings
                    ->map(static fn ($topping): string => (($topping->quantity > 1 ? $topping->quantity.'x ' : '').trim((string) $topping->topping_name)))
                    ->filter(static fn (string $label): bool => trim($label) !== '')
                    ->sort()
                    ->values();
                // Same rule as the aggregated list: other toppings / ice / sugar mean a separate line.
                $itemKey = implode('|', [$item->item_name, $item->size_name ?? '', $toppingLabels->join(';'), $item->ice_percent ?? '', $item->sugar_percent ?? '']);
                if (! $dept['items']->has($itemKey)) {
                    $dept['items']->put($itemKey, [
                        'name' => $item->item_name,
                        'size' => $item->size_name,
                        'unit_price' => $item->unit_price,
                        'quantity' => 0,
                        'total_amount' => 0,
                        'notes' => collect(),
                        'members' => collect(),
                        'toppings' => $toppingLabels,
                        'ice_percent' => $item->ice_percent,
                        'sugar_percent' => $item->sugar_percent,
                    ]);
                }
                $deptItem = $dept['items']->get($itemKey);
                $deptItem['quantity'] += $item->quantity;
                $deptItem['total_amount'] += $item->line_subtotal;
                if (! empty($item->note)) {
                    $deptItem['notes']->push($item->note);
                }
                $memberName = $user?->display_name ?? __('admin.member');
                $deptItem['members']->push($memberName . ($item->quantity > 1 ? " (x{$item->quantity})" : ''));
                $dept['items']->put($itemKey, $deptItem);

                $dept['total_quantity'] += $item->quantity;
                $dept['total_amount'] += $item->line_subtotal;
            }

            $departmentGroups->put($department, $dept);
        }

        return [
            'room' => $room,
            'campaign' => $campaign,
            'orders' => $orders,
            'aggregatedItems' => $aggregatedItems->values(),
            'departmentGroups' => $departmentGroups
                ->sort(static fn (array $a, array $b): int => [$a['is_unassigned'], mb_strtolower($a['department'])] <=> [$b['is_unassigned'], mb_strtolower($b['department'])])
                ->values(),
            'totalUsersCount' => $totalUsersCount,
            'orderedUsersCount' => $orderedUsersCount,
            'declinedUsersCount' => $declinedUsersCount,
            'pendingUsersCount' => $pendingUsersCount,
            'declinedUsers' => $declinedUsers,
            'unresponsiveUsers' => $unresponsiveUsers,
            'grossSubtotal' => $grossSubtotal,
            'grossTotal' => $grossTotal,
            'sponsorSubsidy' => $sponsorSubsidy,
            'netPayables' => $netPayables,
            'paidViaQr' => $paidViaQr,
            'memberDebt' => $memberDebt,
            'sponsorsList' => $sponsorsList,
        ];
    }

    /**
     * Build the fresh summary shown in the close-campaign confirmation modal.
     *
     * Uses the same rules as getCampaignViewData() (cancelled orders excluded, only active room members
     * counted, same sponsor/total formulas) but only loads the columns it needs, because the modal
     * re-fetches it every time an admin opens it.
     *
     * Item groups do not overlap: proxy items live in child orders (parent_id set) and can never be
     * self-paid, self-paid items are flagged per item, and own items are everything else.
     *
     * @param Room $room Room that owns the campaign.
     * @param Campaign $campaign Campaign about to be closed.
     * @return array<string, int|string|bool|null> Campaign identity, item/member counts and money totals (VND).
     */
    public function getCloseSummary(Room $room, Campaign $campaign): array
    {
        $campaign->refresh();

        $orders = $campaign->orders()
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->whereNull('cancelled_at')
            ->get(['id', 'parent_id', 'room_user_id', 'subtotal', 'sponsor_amount']);
        $proxyOrderIds = $orders->whereNotNull('parent_id')->pluck('id')->all();
        $items = OrderItem::query()
            ->whereIn('order_id', $orders->pluck('id'))
            ->get(['order_id', 'quantity', 'line_subtotal', 'is_self_paid']);

        $proxyItems = (int) $items->whereIn('order_id', $proxyOrderIds)->sum('quantity');
        $selfPaidLines = $items->whereNotIn('order_id', $proxyOrderIds)->where('is_self_paid', true);
        $selfPaidItems = (int) $selfPaidLines->sum('quantity');
        // Same base as CloseCampaignAction::splitSelfPaidSubtotal(): line subtotals before fee/discount split.
        $selfPaidTotal = (int) $selfPaidLines->sum('line_subtotal');
        $totalItems = (int) $items->sum('quantity');

        $activeRoomUserIds = $room->roomUsers()->where('status', RoomUserStatus::Active)->pluck('id');
        $orderedUserIds = $orders->pluck('room_user_id')->filter()->unique();
        $declinedUsersCount = CampaignParticipant::query()
            ->where('campaign_id', $campaign->id)
            ->where('status', CampaignParticipant::STATUS_DECLINED)
            ->whereIn('room_user_id', $activeRoomUserIds)
            ->whereNotIn('room_user_id', $orderedUserIds)
            ->distinct()
            ->count('room_user_id');
        $pendingUsersCount = max(0, $activeRoomUserIds->diff($orderedUserIds)->count() - $declinedUsersCount);

        $grossSubtotal = (int) $orders->sum('subtotal');
        $deliveryFee = (int) ($campaign->delivery_fee ?? 0);
        $discount = (int) ($campaign->discount ?? 0);
        $grossTotal = max(0, $grossSubtotal + $deliveryFee - $discount);
        $sponsorTotal = $campaign->sponsor_type === Campaign::SPONSOR_TYPE_FULL
            ? $grossTotal
            : (int) $orders->sum('sponsor_amount');

        return [
            'id' => (int) $campaign->id,
            'code' => $campaign->code,
            'name' => (string) $campaign->name,
            'is_closable' => in_array($campaign->status, [CampaignStatus::Active, CampaignStatus::Closing], true),
            'own_items' => $totalItems - $proxyItems - $selfPaidItems,
            'proxy_items' => $proxyItems,
            'self_paid_items' => $selfPaidItems,
            'total_items' => $totalItems,
            'gross_subtotal' => $grossSubtotal,
            'discount_total' => $discount,
            'extra_fee_total' => $deliveryFee,
            'sponsor_total' => $sponsorTotal,
            'self_paid_total' => $selfPaidTotal,
            'final_total' => max(0, $grossTotal - $sponsorTotal),
            'orders_count' => $orders->count(),
            'ordered_users_count' => $orderedUserIds->count(),
            'pending_users_count' => $pendingUsersCount,
            'declined_users_count' => $declinedUsersCount,
            'total_users_count' => $activeRoomUserIds->count(),
        ];
    }

    /**
     * Normalize a desk location so that spacing and letter case differences fall into one group.
     *
     * @param string|null $deskLocation Raw desk_location value from the member's global profile.
     * @return string Trimmed value with collapsed whitespace, or an empty string when no location is set.
     */
    public static function normalizeDeskLocation(?string $deskLocation): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $deskLocation));
    }
}
