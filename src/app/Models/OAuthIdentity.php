<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OAuthIdentity extends Model
{
    protected $table = 'oauth_identities';
    protected $fillable = ['global_user_id', 'provider', 'provider_user_id', 'provider_email', 'linked_at', 'last_login_at'];

    protected function casts(): array
    {
        return ['linked_at' => 'datetime', 'last_login_at' => 'datetime'];
    }

    public function globalUser(): BelongsTo
    {
        return $this->belongsTo(GlobalUser::class);
    }
}
