<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\DebtStatus;
use App\Enums\RoomUserStatus;
use App\Models\Room;
use App\Models\RoomUser;
use App\Models\RoomUserDevice;

class AdminRoomUserService
{
    /**
     * Get member counts and summary metrics for Room Users directory view.
     *
     * @param Room $room Room entity.
     * @return array<string, int>
     */
    public function getDirectoryMetrics(Room $room): array
    {
        return [
            'totalMembers' => $room->roomUsers()->count(),
            'activeMembers' => $room->roomUsers()->where('status', RoomUserStatus::Active->value)->count(),
            'blockedMembers' => $room->roomUsers()->where('status', RoomUserStatus::Blocked->value)->count(),
            'debtMembers' => $room->roomUsers()->whereHas('debts', fn ($q) => $q->whereIn('status', DebtStatus::outstandingValues()))->count(),
        ];
    }

    /**
     * Format room user detail with masked device trust indicators.
     *
     * @param RoomUser $roomUser Room user entity.
     * @return RoomUser Fresh loaded instance with mapped devices relation.
     */
    public function formatUserDetail(RoomUser $roomUser): RoomUser
    {
        $roomUser->load(['globalUser', 'orders.items.toppings', 'debts.campaign', 'devices']);
        $roomUser->setRelation(
            'devices',
            $roomUser->devices->map(fn (RoomUserDevice $device) => [
                'id' => $device->id,
                'device_uuid' => substr($device->device_uuid, 0, 8) . '…',
                'verified_at' => $device->verified_at,
                'last_seen_at' => $device->last_seen_at,
                'revoked_at' => $device->revoked_at,
                'status' => $device->revoked_at ? 'revoked' : 'active',
            ])
        );

        return $roomUser;
    }
}
