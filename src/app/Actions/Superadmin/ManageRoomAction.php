<?php

declare(strict_types=1);

namespace App\Actions\Superadmin;

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
     * Handle the set status operation.
     * @param Room $room Parameter value.
     * @param string $status Parameter value.
     * @return Room Result of the operation.
     * @throws ValidationException If archiving a room that still has outstanding debts.
     */
    public function setStatus(Room $room, string $status): Room
    {
        if ($status === 'archived' && $room->hasOutstandingDebts()) {
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
     * Delete a room if no members have outstanding debts.
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
            $roomName = $room->name;
            $room->delete();
            app(AuditService::class)->record('room.deleted', 'room', $roomId, $roomId, ['name' => $roomName], []);
        });
    }
}
