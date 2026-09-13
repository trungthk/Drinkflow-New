<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoomUserStatus;
use App\Models\Concerns\BelongsToRoom;
use App\Models\Concerns\HasNormalizedName;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomUser extends Model
{
    use HasStatus, HasNormalizedName, BelongsToRoom;

    protected $fillable = [
        'room_id',
        'global_user_id',
        'user_code',
        'display_name',
        'normalized_name',
        'status',
        'joined_at',
        'last_active_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at'      => 'datetime',
            'last_active_at' => 'datetime',
            'status'         => RoomUserStatus::class,
        ];
    }

    /**
     * Room relationship provided via BelongsToRoom trait.
     * Override kept here to clarify the explicit FK is `room_id`.
     */

    public function globalUser(): BelongsTo
    {
        return $this->belongsTo(GlobalUser::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(RoomUserDevice::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function debts(): HasMany
    {
        return $this->hasMany(Debt::class);
    }
}
