<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CampaignStatus;
use App\Models\Concerns\BelongsToRoom;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasStatus, BelongsToRoom;

    protected $fillable = ['room_id', 'code', 'name', 'restaurant', 'creator_admin_id', 'sponsor_name', 'sponsor_type', 'sponsor_description', 'sponsor_allocations', 'deadline', 'max_budget', 'flat_price', 'delivery_fee', 'discount', 'payment_account_id', 'description', 'status', 'started_at', 'closed_at'];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::creating(function (Campaign $campaign): void {
            if (empty($campaign->code)) {
                $campaign->code = \App\Services\Code\CodeGeneratorService::generateCampaignCode();
            }
        });
    }

    protected function casts(): array
    {
        return ['deadline' => 'datetime', 'started_at' => 'datetime', 'closed_at' => 'datetime', 'status' => CampaignStatus::class, 'sponsor_allocations' => 'array'];
    }

    /**
     * Determine whether this campaign currently accepts new orders.
     *
     * @return bool True when the campaign is live and its ordering deadline has not passed.
     */
    public function isOrderable(): bool
    {
        return $this->status === CampaignStatus::Active
            && ($this->deadline === null || $this->deadline->isFuture());
    }

    public function items(): HasMany
    {
        return $this->hasMany(CampaignItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function debts(): HasMany
    {
        return $this->hasMany(Debt::class);
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class);
    }
}
