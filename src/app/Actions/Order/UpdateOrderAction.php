<?php

namespace App\Actions\Order;

use App\Models\Order;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateOrderAction
{
    /**
     * Handle the execute operation.
     * @param Order $order Parameter value.
     * @param array $data Parameter value.
     * @return Order Result of the operation.
     */
    public function execute(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            if (! $order->status->isActive()) {
                throw ValidationException::withMessages(['order' => 'Chá»‰ Ä‘Æ°á»£c sá»­a order Ä‘ang hoáº¡t Ä‘á»™ng.']);
            }
            $before = $order->only(['payment_method', 'note']);
            $order->update($data);
            $updated = $order->fresh(['roomUser.globalUser', 'items.toppings', 'campaign']);
            app(AuditService::class)->record('order.updated', 'order', $order->id, $order->room_id, $before, $updated->only(['payment_method', 'note']));

            return $updated;
        });
    }
}
