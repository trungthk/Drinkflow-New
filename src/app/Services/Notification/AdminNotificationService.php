<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Models\AdminAccount;
use App\Models\AdminNotification;
use App\Models\AuditLog;
use App\Models\Room;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AdminNotificationService
{
    /**
     * Get unread personal notifications for one admin and room.
     *
     * @param AdminAccount $admin Recipient admin account.
     * @param Room $room Active room scope.
     * @param int $limit Maximum notifications to return.
     * @return Collection<int, AdminNotification> Unread notifications for the recipient.
     */
    public function unreadForRoom(AdminAccount $admin, Room $room, int $limit = 5): Collection
    {
        if (!Schema::hasTable('admin_notifications')) {
            return collect();
        }

        return AdminNotification::query()
            ->where('admin_id', $admin->id)
            ->where('room_id', $room->id)
            ->whereNull('read_at')
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Mark every unread audit notification in a room as read for one admin.
     *
     * @param AdminAccount $admin Recipient admin account.
     * @param Room $room Active room scope.
     * @return int Number of receipts created.
     */
    public function markAllReadForRoom(AdminAccount $admin, Room $room): int
    {
        if (!Schema::hasTable('admin_notifications')) {
            return 0;
        }

        return AdminNotification::query()
            ->where('admin_id', $admin->id)
            ->where('room_id', $room->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
