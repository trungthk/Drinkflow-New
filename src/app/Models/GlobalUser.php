<?php

namespace App\Models;

use App\Enums\GlobalUserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class GlobalUser extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'global_users';

    protected $fillable = [
        'name',
        'normalized_name',
        'email',
        'avatar_url',
        'status',
        'last_login_at',
        'phone',
        'desk_location',
        'delivery_location',
        'preferences',
    ];

    protected $hidden = ['remember_token'];

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'status' => GlobalUserStatus::class,
            'preferences' => 'array',
        ];
    }

    public function getAvatarUrlAttribute(?string $value): string
    {
        return !empty($value) ? $value : asset('images/default-avatar.svg');
    }

    public function oauthIdentities(): HasMany
    {
        return $this->hasMany(OAuthIdentity::class);
    }

    public function roomUsers(): HasMany
    {
        return $this->hasMany(RoomUser::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id')->where('actor_type', 'user');
    }
}
