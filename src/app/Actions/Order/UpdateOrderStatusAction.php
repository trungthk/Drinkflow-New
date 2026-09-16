<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Enums\OrderStatus;
use App\Events\OrderUpdated;
use App\Models\Order;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateOrderStatusAction
{
    /** @var array<string, array<string>> */
    private array $allowed = [
        OrderStatus::Submitted->value => [
            OrderStatus::Confirmed->value,
            OrderStatus::Completed->value,
            OrderStatus::Cancelled->value,
        ],
        OrderStatus::Confirmed->value => [
            OrderStatus::Submitted->value,
            OrderStatus::Ordering->value,
            OrderStatus::Delivering->value,
            OrderStatus::Completed->value,
            OrderStatus::Cancelled->value,
        ],
        OrderStatus::Ordering->value => [
            OrderStatus::Confirmed->value,
            OrderStatus::Ordered->value,
            OrderStatus::Completed->value,
            OrderStatus::Cancelled->value,
        ],
        OrderStatus::Ordered->value => [
            OrderStatus::Delivering->value,
            OrderStatus::Completed->value,
            OrderStatus::Cancelled->value,
        ],
        OrderStatus::Delivering->value => [
            OrderStatus::Confirmed->value,
            OrderStatus::Completed->value,
            OrderStatus::Cancelled->value,
        ],
        OrderStatus::Completed->value => [
            OrderStatus::Confirmed->value,
            OrderStatus::Submitted->value,
            OrderStatus::Cancelled->value,
        ],
        OrderStatus::Cancelled->value => [],
    ];

    /**
     * Transition order status according to state machine rules.
     *
     * @param Order $order Order instance to transition.
     * @param string $next Target status string.
     * @return Order Updated order instance.
     * @throws ValidationException If transition is not permitted.
     */
    public function execute(Order $order, string $next): Order
    {
        [$updated, $previous, $changed] = DB::transaction(function () use ($order, $next): array {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $current = $locked->status->value;

            if ($current === $next) {
                return [$locked, $current, false];
            }

            if (! in_array($next, $this->allowed[$current] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => __('admin.invalid_order_status_transition'),
                ]);
            }
            $locked->status = OrderStatus::from($next);
            if ($next === OrderStatus::Completed->value) {
                $locked->completed_at = now();
            } elseif ($current === OrderStatus::Completed->value) {
                $locked->completed_at = null;
            }

            if ($next === OrderStatus::Cancelled->value) {
                $locked->cancelled_at = now();
            } elseif ($current === OrderStatus::Cancelled->value) {
                $locked->cancelled_at = null;
            }

            $locked->save();
            app(AuditService::class)->record('order.status_updated', 'order', $locked->id, $locked->room_id, ['status' => $current], ['status' => $next]);

            return [$locked->fresh(), $current, true];
        });

        if ($changed) {
            OrderUpdated::dispatch($updated, $previous);
        }

        return $updated;
    }
}

