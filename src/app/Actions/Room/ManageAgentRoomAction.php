<?php

declare(strict_types=1);

namespace App\Actions\Room;

use App\Enums\RoomStatus;
use App\Models\Admin;
use App\Models\Room;
use App\Services\Audit\AuditService;
use App\Services\Room\RoomQuotaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * An Agent creating, editing, archiving and restoring the rooms it owns.
 *
 * Creating or restoring a room takes a quota slot, so both lock the Agent row and check the
 * quota inside the same transaction (the server-side limit; the disabled button is only UX).
 */
class ManageAgentRoomAction
{
    /** @var array<int, string> Attributes written to the audit log. */
    private const AUDITED = ['name', 'slug', 'description', 'status', 'timezone', 'language'];

    public function __construct(
        private readonly AuditService $audit,
        private readonly RoomQuotaService $quota,
    ) {}

    /**
     * Create a room owned by the Agent.
     *
     * @param Admin $owner Agent creating the room.
     * @param array{name: string, slug: string, description?: string|null, timezone?: string, language?: string, status?: string} $data Validated data.
     * @return Room Created room.
     * @throws ValidationException When the room quota is used up.
     */
    public function create(Admin $owner, array $data): Room
    {
        return DB::transaction(function () use ($owner, $data): Room {
            $owner = Admin::query()->lockForUpdate()->findOrFail($owner->id);
            $this->quota->ensureCanAdd($owner);

            $room = Room::create(['owner_admin_id' => $owner->id] + $data + ['status' => RoomStatus::Active->value]);
            $room->admins()->syncWithoutDetaching([$owner->id]);
            $this->audit->record('room.created', 'room', $room->id, null, [], $this->snapshot($room));

            return $room;
        });
    }

    /**
     * Update a room; switching between active and disabled keeps the quota slot.
     *
     * @param Room $room Owned room (not archived).
     * @param array<string, mixed> $data Validated data.
     * @return Room Updated room.
     * @throws ValidationException When the room is archived (restore it first).
     */
    public function update(Room $room, array $data): Room
    {
        return DB::transaction(function () use ($room, $data): Room {
            $room = Room::query()->lockForUpdate()->findOrFail($room->id);
            if ($room->status === RoomStatus::Archived) {
                throw ValidationException::withMessages(['room' => __('platform.rooms.archived_read_only')]);
            }

            $before = $this->snapshot($room);
            $room->update($data);
            $after = $this->snapshot($room);
            if ($before !== $after) {
                $this->audit->record('room.updated', 'room', $room->id, $room->id, $before, $after);
            }

            return $room;
        });
    }

    /**
     * Archive a room, freeing its quota slot.
     *
     * @param Room $room Owned room.
     * @return Room Archived room.
     * @throws ValidationException When debts are outstanding or a campaign is running.
     */
    public function archive(Room $room): Room
    {
        return DB::transaction(function () use ($room): Room {
            $room = Room::query()->lockForUpdate()->findOrFail($room->id);
            if ($room->status === RoomStatus::Archived) {
                return $room;
            }
            if ($room->hasOutstandingDebts()) {
                throw ValidationException::withMessages(['room' => __('admin.cannot_delete_room_with_outstanding_debt')]);
            }
            if ($room->hasActiveCampaign()) {
                throw ValidationException::withMessages(['room' => __('platform.rooms.running_campaign')]);
            }

            $before = $room->status->value;
            $room->update(['status' => RoomStatus::Archived->value]);
            $this->audit->record('room.archived', 'room', $room->id, $room->id, ['status' => $before], ['status' => RoomStatus::Archived->value]);

            return $room;
        });
    }

    /**
     * Restore an archived room as active; it takes a quota slot again.
     *
     * @param Admin $owner Owning Agent.
     * @param Room $room Archived room.
     * @return Room Restored room.
     * @throws ValidationException When the quota is used up.
     */
    public function restore(Admin $owner, Room $room): Room
    {
        return DB::transaction(function () use ($owner, $room): Room {
            $owner = Admin::query()->lockForUpdate()->findOrFail($owner->id);
            $room = Room::query()->lockForUpdate()->findOrFail($room->id);
            if ($room->status !== RoomStatus::Archived) {
                return $room;
            }

            $this->quota->ensureCanAdd($owner);
            $room->update(['status' => RoomStatus::Active->value]);
            $this->audit->record('room.restored', 'room', $room->id, $room->id, ['status' => RoomStatus::Archived->value], ['status' => RoomStatus::Active->value]);

            return $room;
        });
    }

    /**
     * Audited attributes of a room.
     *
     * @param Room $room Room.
     * @return array<string, mixed> Values (status as its value).
     */
    private function snapshot(Room $room): array
    {
        $values = $room->only(self::AUDITED);
        $values['status'] = $room->status->value;

        return $values;
    }
}
