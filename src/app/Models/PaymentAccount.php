<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentAccountStatus;
use App\Models\Concerns\BelongsToRoom;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Model;

class PaymentAccount extends Model
{
    use HasStatus, BelongsToRoom;

    protected $fillable = ['room_id', 'bank_code', 'bank_name', 'account_number', 'account_name', 'is_default', 'status'];

    protected $hidden = ['account_number'];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'status'     => PaymentAccountStatus::class,
        ];
    }
}
