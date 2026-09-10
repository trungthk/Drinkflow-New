<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAccount extends Model
{
    protected $fillable = ['room_id', 'bank_code', 'bank_name', 'account_number', 'account_name', 'is_default', 'status'];
    protected $hidden = ['account_number'];
    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
