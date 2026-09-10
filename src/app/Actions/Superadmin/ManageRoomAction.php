<?php

namespace App\Actions\Superadmin;

use App\Models\Room;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class ManageRoomAction
{
    public function create(array $data): Room
    {
        return DB::transaction(function () use ($data): Room {
            $room = Room::create($data);
            app(AuditService::class)->record('room.created', 'room', $room->id, null, [], $room->only(['name', 'slug', 'status', 'timezone', 'language']));
            return $room->fresh();
        });
    }

    public function update(Room $room, array $data): Room
    {
        return DB::transaction(function () use ($room, $data): Room {
            $before = $room->only(['name', 'slug', 'description', 'avatar_url', 'status', 'timezone', 'language', 'settings']);
            $room->update($data);
            app(AuditService::class)->record('room.updated', 'room', $room->id, $room->id, $before, $room->fresh()->only(array_keys($before)));
            return $room->fresh();
        });
    }

    public function setStatus(Room $room, string $status): Room
    {
        return DB::transaction(function () use ($room, $status): Room {
            $before = $room->status;
            $room->update(['status' => $status]);
            app(AuditService::class)->record('room.status_updated', 'room', $room->id, $room->id, ['status' => $before], ['status' => $status]);
            return $room->fresh();
        });
    }
}
