<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DebtStatus;
use App\Models\Concerns\BelongsToRoom;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Debt extends Model
{
    use HasStatus, BelongsToRoom;

    protected $fillable = ['room_id', 'code', 'campaign_id', 'room_user_id', 'original_amount', 'sponsor_amount', 'sponsor_type', 'sponsor_description', 'adjustment_amount', 'paid_amount', 'remaining_amount', 'status', 'payment_requested_at', 'note'];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::creating(function (Debt $debt): void {
            if (empty($debt->code)) {
                $debt->code = \App\Services\Code\CodeGeneratorService::generateDebtCode();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => DebtStatus::class,
            'payment_requested_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function roomUser(): BelongsTo
    {
        return $this->belongsTo(RoomUser::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(DebtAdjustment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(DebtPayment::class);
    }
}
