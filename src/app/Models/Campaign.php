<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\CampaignStatus;

class Campaign extends Model
{
    protected $fillable = ['room_id', 'name', 'restaurant', 'creator_admin_id', 'sponsor_name', 'deadline', 'max_budget', 'flat_price', 'delivery_fee', 'discount', 'payment_account_id', 'description', 'status', 'started_at', 'closed_at'];

    protected function casts(): array
    {
        return ['deadline' => 'datetime', 'started_at' => 'datetime', 'closed_at' => 'datetime', 'status' => CampaignStatus::class];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
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
