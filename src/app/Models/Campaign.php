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

    protected $fillable = ['room_id', 'name', 'restaurant', 'creator_admin_id', 'sponsor_name', 'sponsor_type', 'sponsor_description', 'deadline', 'max_budget', 'flat_price', 'delivery_fee', 'discount', 'payment_account_id', 'description', 'status', 'started_at', 'closed_at'];

    protected function casts(): array
    {
        return ['deadline' => 'datetime', 'started_at' => 'datetime', 'closed_at' => 'datetime', 'status' => CampaignStatus::class];
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
