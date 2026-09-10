<?php

namespace App\Actions\Order;

use App\Enums\OrderStatus;
use App\Events\OrderUpdated;
use App\Models\Order;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateOrderStatusAction
{
    private array $allowed = ['submitted' => ['confirmed', 'cancelled'], 'confirmed' => ['ordering', 'cancelled'], 'ordering' => ['ordered', 'cancelled'], 'ordered' => ['delivering', 'completed'], 'delivering' => ['completed'], 'completed' => [], 'cancelled' => []];

    public function execute(Order $order, string $next): Order
    {
        [$updated, $previous] = DB::transaction(function () use ($order, $next): array {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $current = $locked->status->value;
            if (! in_array($next, $this->allowed[$current] ?? [], true)) {
                throw ValidationException::withMessages(['status' => 'Chuyển trạng thái order không hợp lệ.']);
            }
            $locked->status = OrderStatus::from($next);
            if ($next === 'completed') {
                $locked->completed_at = now();
            }
            if ($next === 'cancelled') {
                $locked->cancelled_at = now();
            }
            $locked->save();
            app(AuditService::class)->record('order.status_updated', 'order', $locked->id, $locked->room_id, ['status' => $current], ['status' => $next]);

            return [$locked->fresh(), $current];
        });
        OrderUpdated::dispatch($updated, $previous);

        return $updated;
    }
}
