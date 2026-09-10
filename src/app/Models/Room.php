<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'avatar_url', 'status', 'timezone', 'language', 'settings'];

    protected function casts(): array { return ['settings' => 'array']; }

    public function roomUsers(): HasMany { return $this->hasMany(RoomUser::class); }

    public function campaigns(): HasMany { return $this->hasMany(Campaign::class); }

    public function paymentAccounts(): HasMany { return $this->hasMany(PaymentAccount::class); }
}
