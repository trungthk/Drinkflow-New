<?php

declare(strict_types=1);

namespace App\Services\Room;

use App\Models\Admin;
use App\Models\Room;
use App\Services\Audit\AuditService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Room ownership: which Agent owns a room (subscription, quota, billing).
 *
 * The owner always keeps operational access through `admin_rooms`; collaborators in that table
 * never decide ownership. Rooms that could not be mapped automatically are reported, never guessed.
 */
class RoomOwnershipService
{
    public function __construct(private readonly AuditService $audit) {}

    /**
     * Make the Admin the owner of the room and give them operational access.
     *
     * @param Room $room Room.
     * @param Admin $owner New owner.
     * @return Room Updated room.
     */
    public function assign(Room $room, Admin $owner): Room
    {
        return DB::transaction(function () use ($room, $owner): Room {
            $room = Room::query()->lockForUpdate()->findOrFail($room->id);
            $previous = $room->owner_admin_id;
            $room->update(['owner_admin_id' => $owner->id]);
            $room->admins()->syncWithoutDetaching([$owner->id]);
            if ((int) $previous !== (int) $owner->id) {
                $this->audit->record('room.owner_changed', 'room', $room->id, $room->id, ['owner_admin_id' => $previous], ['owner_admin_id' => $owner->id]);
            }

            return $room;
        });
    }

    /**
     * Keep the owner in a new operational admin list, so removing collaborators never locks the owner out.
     *
     * @param Room $room Room.
     * @param array<int, int> $adminIds Requested admin IDs.
     * @return array<int, int> Admin IDs including the owner.
     */
    public function withOwner(Room $room, array $adminIds): array
    {
        $ids = array_map('intval', $adminIds);
        if ($room->owner_admin_id !== null && ! in_array((int) $room->owner_admin_id, $ids, true)) {
            $ids[] = (int) $room->owner_admin_id;
        }

        return array_values(array_unique($ids));
    }

    /**
     * Rooms without owner, with the Admins that could own them (for a manual decision).
     *
     * @return Collection<int, Room> Unresolved rooms with their `admins` loaded.
     */
    public function unresolved(): Collection
    {
        return Room::query()->whereNull('owner_admin_id')->with('admins:admins.id,admins.name,admins.email')->orderBy('id')->get();
    }
}
