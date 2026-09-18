<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\DebtStatus;
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
            'orders.items.toppings',
            'debts.roomUser.globalUser',
        ]);

        $orders = $campaign->orders->whereNotIn('status', [OrderStatus::Cancelled->value]);
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
                $key = $item->item_name . '|' . ($item->size_name ?? '');
                if (! $aggregatedItems->has($key)) {
                    $aggregatedItems->put($key, [
                        'name' => $item->item_name,
                        'size' => $item->size_name,
                        'unit_price' => $item->unit_price,
                        'quantity' => 0,
                        'total_amount' => 0,
                        'notes' => collect(),
                        'toppings' => collect(),
                    ]);
                }
                $curr = $aggregatedItems->get($key);
                $curr['quantity'] += $item->quantity;
                $curr['total_amount'] += $item->line_subtotal;
                if (! empty($item->note)) {
                    $curr['notes']->push($item->note);
                }
                foreach ($item->toppings as $topping) {
                    $label = trim((string) $topping->topping_name);
                    if ($label !== '') {
                        $curr['toppings']->push(($topping->quantity > 1 ? $topping->quantity.'x ' : '').$label);
                    }
                }
                $aggregatedItems->put($key, $curr);
            }
        }

        $grossSubtotal = (int) $orders->sum('subtotal');
        $sponsorSubsidy = (int) $orders->sum('sponsor_amount');
        $netPayables = (int) $orders->sum('final_amount');
        $paidViaQr = (int) $campaign->debts->sum('paid_amount');
        $memberDebt = (int) $campaign->debts->whereIn('status', [DebtStatus::Unpaid->value, DebtStatus::Partial->value])->sum('remaining_amount');

        $sponsorAllocationsData = collect($campaign->sponsor_allocations ?? []);
        $sponsorUserIds = $sponsorAllocationsData->pluck('room_user_id')->filter()->map(fn($id) => (int) $id);
        $sponsorRoomUsers = $sponsorUserIds->isNotEmpty()
            ? $room->roomUsers()->with('globalUser')->whereIn('id', $sponsorUserIds)->get()->keyBy('id')
            : collect();

        $sponsorsList = $sponsorAllocationsData->map(function ($alloc) use ($sponsorRoomUsers, $sponsorSubsidy) {
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
        });

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
            $department = trim((string) ($globalUser?->desk_location ?? '')) ?: __('admin.unassigned_department');

            if (! $departmentGroups->has($department)) {
                $departmentGroups->put($department, [
                    'department' => $department,
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
                $itemKey = $item->item_name . '|' . ($item->size_name ?? '');
                if (! $dept['items']->has($itemKey)) {
                    $dept['items']->put($itemKey, [
                        'name' => $item->item_name,
                        'size' => $item->size_name,
                        'unit_price' => $item->unit_price,
                        'quantity' => 0,
                        'total_amount' => 0,
                        'notes' => collect(),
                        'members' => collect(),
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
            'departmentGroups' => $departmentGroups->values(),
            'totalUsersCount' => $totalUsersCount,
            'orderedUsersCount' => $orderedUsersCount,
            'declinedUsersCount' => $declinedUsersCount,
            'pendingUsersCount' => $pendingUsersCount,
            'declinedUsers' => $declinedUsers,
            'unresponsiveUsers' => $unresponsiveUsers,
            'grossSubtotal' => $grossSubtotal,
            'sponsorSubsidy' => $sponsorSubsidy,
            'netPayables' => $netPayables,
            'paidViaQr' => $paidViaQr,
            'memberDebt' => $memberDebt,
            'sponsorsList' => $sponsorsList,
        ];
    }
}
