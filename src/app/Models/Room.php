<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Room extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'avatar_url', 'status', 'timezone', 'language', 'settings'];

    protected function casts(): array
    {
        return ['settings' => 'array'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if ($field) {
            return $this->where($field, $value)->first();
        }

        if (is_numeric($value)) {
            return $this->where('id', (int) $value)->orWhere('slug', (string) $value)->first();
        }

        return $this->where('slug', (string) $value)->first();
    }

    public function roomUsers(): HasMany
    {
        return $this->hasMany(RoomUser::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function paymentAccounts(): HasMany
    {
        return $this->hasMany(PaymentAccount::class);
    }

    public function roomSettings(): HasMany
    {
        return $this->hasMany(RoomSetting::class);
    }

    public function notificationChannels(): HasMany
    {
        return $this->hasMany(NotificationChannel::class);
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(AdminAccount::class, 'admin_rooms', 'room_id', 'admin_id');
    }
}
