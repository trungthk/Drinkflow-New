<?php

namespace App\Actions\Order;

use App\Events\OrderDeleted;
use App\Models\Order;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteOrderAction
{
    public function execute(Order $order): void
    {
        $payload = DB::transaction(function () use ($order): array {
            $order = Order::query()->with('roomUser')->lockForUpdate()->findOrFail($order->id);
            if ($order->status->value === 'completed') {
                throw ValidationException::withMessages(['order' => 'Không thể xóa order đã hoàn tất.']);
            }
            $data = ['order_id' => $order->id, 'room_id' => $order->room_id, 'room_user_id' => $order->room_user_id, 'global_user_id' => $order->roomUser->global_user_id];
            app(AuditService::class)->record('order.deleted', 'order', $order->id, $order->room_id, $order->toArray(), [], ['reason' => 'admin_delete']);
            $order->delete();

            return $data;
        });

        OrderDeleted::dispatch($payload);
    }
}
