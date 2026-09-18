<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ProxyOrdersCreated
{
    use Dispatchable, SerializesModels;

    /**
     * Create the event after a proxy-order checkout splits into parent + child orders.
     *
     * @param Order $parentOrder The top-level order (may have zero items if requester ordered only for others).
     * @param Collection<int, Order> $childOrders Child orders created on behalf of other room users.
     */
    public function __construct(
        public readonly Order $parentOrder,
        public readonly Collection $childOrders,
    ) {
    }
}