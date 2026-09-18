<?php

declare(strict_types=1);

namespace App\Actions\Campaign;

use App\Enums\OrderStatus;
use App\Events\CampaignDelivering;
use App\Events\OrderUpdated;
use App\Models\Campaign;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class MarkCampaignDeliveringAction
{
    /**
     * Create the action instance.
     *
     * @param AuditService $auditService Audit logger service.
     */
    public function __construct(
        private readonly AuditService $auditService
    ) {
    }

    /**
     * Transition all active orders in campaign to delivering status and notify members.
     *
     * @param Campaign $campaign Campaign entity.
     * @param int|null $adminId Admin executing the action.
     * @return Campaign Fresh campaign instance.
     */
    public function execute(Campaign $campaign, ?int $adminId = null): Campaign
    {
        DB::transaction(function () use ($campaign): void {
            $orders = $campaign->orders()
                ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Completed->value])
                ->get();

            foreach ($orders as $order) {
                $previous = $order->status instanceof \BackedEnum ? $order->status->value : (string) $order->status;
                $order->status = OrderStatus::Delivering;
                $order->save();

                $this->auditService->record(
                    'order.status_updated',
                    'order',
                    $order->id,
                    $order->room_id,
                    ['status' => $previous],
                    ['status' => OrderStatus::Delivering->value]
                );

                OrderUpdated::dispatch($order, $previous);
            }

            $this->auditService->record(
                'campaign.delivering',
                'campaign',
                $campaign->id,
                $campaign->room_id,
                [],
                ['status' => OrderStatus::Delivering->value, 'orders_count' => $orders->count()]
            );
        });

        /** @var Campaign $freshCampaign */
        $freshCampaign = $campaign->fresh(['room', 'orders.roomUser.globalUser', 'items']);
        CampaignDelivering::dispatch($freshCampaign);

        return $freshCampaign;
    }
}
