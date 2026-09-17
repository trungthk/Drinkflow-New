<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'campaign_item_id', 'item_name', 'size_name', 'unit_price', 'quantity', 'ice_percent', 'sugar_percent', 'line_subtotal', 'note'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function toppings(): HasMany
    {
        return $this->hasMany(OrderItemTopping::class);
    }
}
