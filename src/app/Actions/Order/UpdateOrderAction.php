<?php

namespace App\Actions\Order;

use App\Models\Order;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateOrderAction
{
    public function execute(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            if (! $order->status->isActive()) {
                throw ValidationException::withMessages(['order' => 'Chỉ được sửa order đang hoạt động.']);
            }
            $before = $order->only(['payment_method', 'note']);
            $order->update($data);
            $updated = $order->fresh(['roomUser.globalUser', 'items.toppings', 'campaign']);
            app(AuditService::class)->record('order.updated', 'order', $order->id, $order->room_id, $before, $updated->only(['payment_method', 'note']));

            return $updated;
        });
    }
}
