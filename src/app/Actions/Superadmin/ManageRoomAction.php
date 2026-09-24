<?php

declare(strict_types=1);

namespace App\Actions\Superadmin;

use App\Enums\RoomStatus;
use App\Models\Room;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageRoomAction
{
    /**
     * Handle the create operation.
     * @param array<string, mixed> $data Parameter value.
     * @return Room Result of the operation.
     */
    public function create(array $data): Room
    {
        return DB::transaction(function () use ($data): Room {
            $room = Room::create($data);
            app(AuditService::class)->record('room.created', 'room', $room->id, null, [], $room->only(['name', 'slug', 'status', 'timezone', 'language']));
            return $room->fresh();
        });
    }

    /**
     * Handle the update operation.
     * @param Room $room Parameter value.
     * @param array<string, mixed> $data Parameter value.
     * @return Room Result of the operation.
     */
    public function update(Room $room, array $data): Room
    {
        return DB::transaction(function () use ($room, $data): Room {
            $before = $room->only(['name', 'slug', 'description', 'avatar_url', 'status', 'timezone', 'language', 'settings']);
            $room->update($data);
            app(AuditService::class)->record('room.updated', 'room', $room->id, $room->id, $before, $room->fresh()->only(array_keys($before)));
            return $room->fresh();
        });
    }

    /**
     * Replace the set of admins responsible for a room.
     *
     * @param Room $room Room entity.
     * @param array<int, int> $adminIds Admin account IDs to assign; any admin not in this list is unassigned.
     * @return Room Room with its fresh admin list loaded.
     */
    public function syncAdmins(Room $room, array $adminIds): Room
    {
        return DB::transaction(function () use ($room, $adminIds): Room {
            $before = $room->admins()->pluck('admin_accounts.id')->sort()->values()->all();
            $room->admins()->sync($adminIds);
            $after = $room->admins()->pluck('admin_accounts.id')->sort()->values()->all();
            app(AuditService::class)->record('room.admins_updated', 'room', $room->id, $room->id, ['admin_ids' => $before], ['admin_ids' => $after]);
            return $room->fresh('admins');
        });
    }

    /**
     * Handle the set status operation.
     * @param Room $room Parameter value.
     * @param string $status Parameter value.
     * @return Room Result of the operation.
     * @throws ValidationException If archiving a room that still has outstanding debts.
     */
    public function setStatus(Room $room, string $status): Room
    {
        if ($status === RoomStatus::Archived->value && $room->hasOutstandingDebts()) {
            throw ValidationException::withMessages([
                'room' => __('admin.cannot_delete_room_with_outstanding_debt'),
            ]);
        }

        return DB::transaction(function () use ($room, $status): Room {
            $before = $room->status;
            $room->update(['status' => $status]);
            app(AuditService::class)->record('room.status_updated', 'room', $room->id, $room->id, ['status' => $before], ['status' => $status]);
            return $room->fresh();
        });
    }

    /**
     * Delete a room and its room-scoped data if no members have outstanding debts.
     *
     * Debts, orders, campaigns, payment accounts and memberships reference the room
     * with RESTRICT foreign keys, so they are removed explicitly (children first)
     * before the room itself; their own children cascade at database level.
     *
     * @param Room $room Room entity instance.
     * @return void
     * @throws ValidationException If room has outstanding debts.
     */
    public function delete(Room $room): void
    {
        if ($room->hasOutstandingDebts()) {
            throw ValidationException::withMessages([
                'room' => __('admin.cannot_delete_room_with_outstanding_debt'),
            ]);
        }

        DB::transaction(function () use ($room): void {
            $roomId = $room->id;
            $before = $room->only(['name', 'slug', 'status']);

            $room->debts()->delete();
            $room->orders()->whereNotNull('parent_id')->delete();
            $room->orders()->delete();
            $room->campaigns()->delete();
            $room->paymentAccounts()->delete();
            $room->roomUsers()->delete();
            $room->delete();

            // The room row no longer exists, so the audit log must not reference it via room_id.
            app(AuditService::class)->record('room.deleted', 'room', $roomId, null, $before, []);
        });
    }
}
