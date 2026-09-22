<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Enums\OrderStatus;
use App\Events\OrderDeleted;
use App\Models\Debt;
use App\Models\Order;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteOrderAction
{
    /**
     * Delete an uncompleted order from the campaign, along with the member's debt (and its payment
     * records) for that campaign, since a debt only ever covers this single order (one order per
     * member per campaign, enforced by a unique constraint).
     *
     * @param Order $order Order instance to delete.
     * @return void
     * @throws ValidationException If order has already completed.
     */
    public function execute(Order $order): void
    {
        $payload = DB::transaction(function () use ($order): array {
            $order = Order::query()->with('roomUser')->lockForUpdate()->findOrFail($order->id);
            if ($order->status === OrderStatus::Completed) {
                throw ValidationException::withMessages([
                    'order' => __('admin.cannot_delete_completed_order'),
                ]);
            }
            $data = ['order_id' => $order->id, 'room_id' => $order->room_id, 'room_user_id' => $order->room_user_id, 'global_user_id' => $order->roomUser->global_user_id];

            $debt = Debt::query()
                ->where('campaign_id', $order->campaign_id)
                ->where('room_user_id', $order->room_user_id)
                ->first();
            if ($debt) {
                app(AuditService::class)->record('debt.deleted', 'debt', $debt->id, $debt->room_id, $debt->toArray(), [], ['reason' => 'order_deleted']);
                $debt->payments()->delete();
                $debt->delete();
            }

            app(AuditService::class)->record('order.deleted', 'order', $order->id, $order->room_id, $order->toArray(), [], ['reason' => 'admin_delete']);
            $order->delete();

            return $data;
        });

        OrderDeleted::dispatch($payload);
    }
}
