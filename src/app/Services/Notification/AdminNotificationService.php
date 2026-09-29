<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Models\Admin;
use App\Models\Superadmin;
use App\Models\AdminNotification;
use App\Models\Room;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AdminNotificationService
{
    /** Inbox filter value: only notifications not read yet. */
    public const FILTER_UNREAD = 'unread';

    /** Inbox filter value: only notifications already read. */
    public const FILTER_READ = 'read';

    /**
     * Get unread personal notifications for one admin and room.
     *
     * @param Admin $admin Recipient admin account.
     * @param Room $room Active room scope.
     * @param int $limit Maximum notifications to return.
     * @return Collection<int, AdminNotification> Unread notifications for the recipient.
     */
    public function unreadForRoom(Admin $admin, Room $room, int $limit = 5): Collection
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
     * @param Admin $admin Recipient admin account.
     * @param Room $room Active room scope.
     * @return int Number of receipts created.
     */
    public function markAllReadForRoom(Admin $admin, Room $room): int
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

    /**
     * Get the newest unread notifications addressed to one admin account, across every room.
     *
     * Used by the superadmin header bell, which is not scoped to a room.
     *
     * @param Admin|Superadmin $admin Recipient account.
     * @param int $limit Maximum notifications to return.
     * @return Collection<int, AdminNotification> Unread notifications, newest first, with their room loaded.
     */
    public function unreadForAdmin(Admin|Superadmin $admin, int $limit = 5): Collection
    {
        if (!Schema::hasTable('admin_notifications')) {
            return collect();
        }

        return $this->forAdmin($admin)
            ->whereNull('read_at')
            ->with('room:id,name,slug')
            ->latest('created_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Count every unread notification addressed to one admin account.
     *
     * @param Admin|Superadmin $admin Recipient account.
     * @return int Number of unread notifications.
     */
    public function unreadCountForAdmin(Admin|Superadmin $admin): int
    {
        if (!Schema::hasTable('admin_notifications')) {
            return 0;
        }

        return $this->forAdmin($admin)->whereNull('read_at')->count();
    }

    /**
     * Paginate the notifications addressed to one admin account (never other admins' or users' notifications).
     *
     * @param Admin|Superadmin $admin Recipient account.
     * @param array{status?: ?string, search?: ?string} $filters `status` is unread|read (anything else = all); `search` matches title/body.
     * @param int $perPage Page size.
     * @param string $pageName Query-string key for the page number, so it can share a page with another paginator.
     * @return LengthAwarePaginator Notifications, newest first, with their room loaded.
     */
    public function paginateForAdmin(Admin|Superadmin $admin, array $filters, int $perPage, string $pageName = 'page'): LengthAwarePaginator
    {
        if (!Schema::hasTable('admin_notifications')) {
            return new Paginator([], 0, $perPage, 1, ['pageName' => $pageName]);
        }

        $status = $filters['status'] ?? null;
        $search = trim((string) ($filters['search'] ?? ''));

        return $this->forAdmin($admin)
            ->with('room:id,name,slug')
            ->when($status === self::FILTER_UNREAD, static fn (Builder $query) => $query->whereNull('read_at'))
            ->when($status === self::FILTER_READ, static fn (Builder $query) => $query->whereNotNull('read_at'))
            ->when($search !== '', static fn (Builder $query) => $query->where(
                static fn (Builder $inner) => $inner->where('title', 'like', "%{$search}%")->orWhere('body', 'like', "%{$search}%")
            ))
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage, ['*'], $pageName);
    }

    /**
     * Mark every unread notification of one admin account as read, across every room.
     *
     * @param Admin|Superadmin $admin Recipient account.
     * @return int Number of notifications marked as read.
     */
    public function markAllReadForAdmin(Admin|Superadmin $admin): int
    {
        if (!Schema::hasTable('admin_notifications')) {
            return 0;
        }

        return $this->forAdmin($admin)->whereNull('read_at')->update(['read_at' => now()]);
    }

    /**
     * Mark one notification as read, only if it belongs to the given admin account.
     *
     * @param Admin|Superadmin $admin Recipient account.
     * @param AdminNotification $notification Notification to mark.
     * @return bool False when the notification belongs to someone else.
     */
    public function markRead(Admin|Superadmin $admin, AdminNotification $notification): bool
    {
        if ($admin instanceof Superadmin || (int) $notification->admin_id !== (int) $admin->id) {
            return false;
        }

        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        return true;
    }

    /**
     * Base query restricted to notifications addressed to one admin account.
     *
     * Superadmins own no rooms, and every notification is addressed to a room's Admins, so a
     * Superadmin's inbox is always empty: `admin_id` never refers to a `superadmins` row.
     *
     * @param Admin|Superadmin $admin Recipient account.
     * @return Builder<AdminNotification> Scoped query.
     */
    private function forAdmin(Admin|Superadmin $admin): Builder
    {
        if ($admin instanceof Superadmin) {
            return AdminNotification::query()->whereRaw('1 = 0');
        }

        return AdminNotification::query()->where('admin_id', $admin->id);
    }
}
