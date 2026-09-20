<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Concerns\BelongsToRoom;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasStatus, BelongsToRoom;

    protected $fillable = [
        'parent_id',
        'room_id',
        'code',
        'campaign_id',
        'room_user_id',
        'payment_method',
        'subtotal',
        'delivery_amount',
        'discount_amount',
        'sponsor_amount',
        'final_amount',
        'status',
        'payment_status',
        'note',
        'submitted_at',
        'completed_at',
        'cancelled_at',
        'paid_at',
    ];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if (empty($order->code)) {
                $order->code = \App\Services\Code\CodeGeneratorService::generateOrderCode((int) $order->room_id);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'paid_at' => 'datetime',
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
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

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * The parent order (null if this is already a parent order).
     *
     * @return BelongsTo<Order, Order>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'parent_id');
    }

    /**
     * Child orders placed on behalf of other users.
     *
     * @return HasMany<Order>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Order::class, 'parent_id');
    }

    /**
     * Limit the query to orders (including proxy orders placed on behalf of others) of a live (active) campaign.
     *
     * @param Builder<Order> $query Order query being scoped.
     * @return Builder<Order> Scoped query.
     */
    public function scopeInLiveCampaign(Builder $query): Builder
    {
        return $query
            ->whereHas('campaign', static fn (Builder $campaignQuery): Builder => $campaignQuery->where('status', CampaignStatus::Active->value));
    }

    /**
     * Whether this order is a parent (top-level) order.
     *
     * @return bool
     */
    public function isParent(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Whether this order is a child (proxy) order created on behalf of another user.
     *
     * @return bool
     */
    public function isChild(): bool
    {
        return $this->parent_id !== null;
    }
}
