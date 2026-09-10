<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemTopping extends Model
{
    protected $fillable = ['order_item_id', 'campaign_item_topping_id', 'topping_name', 'unit_price', 'quantity', 'subtotal'];
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
