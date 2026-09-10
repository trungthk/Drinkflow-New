<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\RoomUserStatus;

class RoomUser extends Model
{
    protected $fillable = ['room_id', 'global_user_id', 'user_code', 'display_name', 'normalized_name', 'status', 'joined_at', 'last_active_at'];

    protected function casts(): array { return ['joined_at' => 'datetime', 'last_active_at' => 'datetime', 'status' => RoomUserStatus::class]; }

    public function room(): BelongsTo { return $this->belongsTo(Room::class); }

    public function globalUser(): BelongsTo { return $this->belongsTo(GlobalUser::class); }

    public function devices(): HasMany { return $this->hasMany(RoomUserDevice::class); }

    public function orders(): HasMany { return $this->hasMany(Order::class); }

    public function debts(): HasMany { return $this->hasMany(Debt::class); }
}
