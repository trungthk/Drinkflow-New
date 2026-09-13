<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use App\Models\Concerns\BelongsToRoom;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasStatus, BelongsToRoom;

    protected $fillable = ['room_id', 'campaign_id', 'room_user_id', 'payment_method', 'subtotal', 'delivery_amount', 'discount_amount', 'sponsor_amount', 'final_amount', 'status', 'note', 'submitted_at', 'completed_at', 'cancelled_at'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'completed_at' => 'datetime', 'cancelled_at' => 'datetime', 'status' => OrderStatus::class];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function roomUser(): BelongsTo
    {
        return $this->belongsTo(RoomUser::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
