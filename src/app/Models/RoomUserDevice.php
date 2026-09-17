<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomUserDevice extends Model
{
    protected $fillable = ['room_user_id', 'device_uuid', 'token_hash', 'verified_at', 'last_seen_at', 'revoked_at'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime', 'last_seen_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function roomUser(): BelongsTo
    {
        return $this->belongsTo(RoomUser::class);
    }
}
