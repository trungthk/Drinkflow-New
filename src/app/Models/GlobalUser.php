<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GlobalUserStatus;
use App\Models\Concerns\HasNormalizedName;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class GlobalUser extends Authenticatable
{
    use HasFactory, Notifiable, HasStatus, HasNormalizedName;

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
            'status'        => GlobalUserStatus::class,
            'preferences'   => 'array',
        ];
    }

    /**
     * Trả về URL avatar của người dùng, fallback về ảnh mặc định nếu chưa có.
     *
     * @param  string|null  $value  Giá trị avatar_url trong DB.
     * @return string URL ảnh đại diện.
     */
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

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'room_users', 'global_user_id', 'room_id')
            ->withPivot(['id', 'status', 'user_code', 'last_active_at'])
            ->withTimestamps();
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id')->where('actor_type', 'user');
    }

    /**
     * Check if this global user has any outstanding debt in any room (or a specific room).
     *
     * @param int|null $roomId Optional room id to restrict check.
     * @return bool True if outstanding debt exists, false otherwise.
     */
    public function hasOutstandingDebts(?int $roomId = null): bool
    {
        $roomUserIds = $this->roomUsers()
            ->when($roomId !== null, fn ($q) => $q->where('room_id', $roomId))
            ->pluck('id');

        if ($roomUserIds->isEmpty()) {
            return false;
        }

        return \App\Models\Debt::query()
            ->whereIn('room_user_id', $roomUserIds)
            ->whereIn('status', \App\Enums\DebtStatus::outstandingValues())
            ->where('remaining_amount', '>', 0)
            ->exists();
    }
}
