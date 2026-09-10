<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Enums\GlobalUserStatus;

class GlobalUser extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'global_users';

    protected $fillable = ['name', 'normalized_name', 'email', 'avatar_url', 'status', 'last_login_at'];

    protected $hidden = ['remember_token'];

    protected function casts(): array
    {
        return ['last_login_at' => 'datetime', 'status' => GlobalUserStatus::class];
    }

    public function oauthIdentities(): HasMany { return $this->hasMany(OAuthIdentity::class); }

    public function roomUsers(): HasMany { return $this->hasMany(RoomUser::class); }
}
