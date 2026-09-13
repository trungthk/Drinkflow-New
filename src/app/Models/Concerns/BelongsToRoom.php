<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Room;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait cung cấp quan hệ thuộc về Room và các scope truy vấn theo Room.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait BelongsToRoom
{
    /**
     * Mối quan hệ liên kết đến phòng đặt đồ (Room).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Scope lọc bản ghi thuộc về một phòng xác định.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Query Builder instance.
     * @param  \App\Models\Room|int|string  $room  Đối tượng Room, ID hoặc Slug của phòng.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForRoom(Builder $query, Room|int|string $room): Builder
    {
        $roomId = $room instanceof Room ? $room->id : (is_numeric($room) ? (int) $room : null);

        if ($roomId !== null) {
            return $query->where($this->qualifyColumn('room_id'), $roomId);
        }

        return $query->whereHas('room', function (Builder $q) use ($room): void {
            $q->where('slug', (string) $room);
        });
    }
}
