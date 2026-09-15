<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Models\Campaign;
use App\Support\Helpers\FormatHelper;

class CampaignNotificationPayloadService
{
    /**
     * Create a safe, driver-neutral notification payload for a campaign lifecycle event.
     *
     * @param Campaign $campaign Campaign with its room relation loaded.
     * @param string $event Lifecycle event name.
     * @return array<string, mixed> Notification payload.
     */
    public function make(Campaign $campaign, string $event): array
    {
        $room = $campaign->room;
        $title = match ($event) {
            'campaign.created' => __('messages.campaign_created_title'),
            'campaign.cancelled' => __('messages.campaign_cancelled_title'),
            default => __('messages.campaign_closed_title'),
        };
        $orderUrl = $event === 'campaign.created'
            ? route('user.campaigns.order-page', [$room, $campaign])
            : null;

        return [
            'event' => $event,
            'title' => $title,
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'restaurant' => $campaign->restaurant,
                'deadline' => $campaign->deadline?->toIso8601String(),
                'sponsor_name' => $campaign->sponsor_name,
                'sponsor_type' => $campaign->sponsor_type,
                'sponsorship_amount' => $campaign->max_budget,
                'max_product_budget' => $campaign->max_budget,
                'order_url' => $orderUrl,
            ],
            'message' => $this->message($title, $campaign, $orderUrl),
        ];
    }

    /**
     * Format a compact human-readable message for chat notification drivers.
     *
     * @param string $title Notification title.
     * @param Campaign $campaign Campaign data.
     * @param ?string $orderUrl User order link when ordering is available.
     * @return string Formatted notification text.
     */
    private function message(string $title, Campaign $campaign, ?string $orderUrl): string
    {
        $lines = [$title, __('messages.campaign_name', ['name' => $campaign->name])];
        $lines[] = __('messages.campaign_deadline', [
            'date' => $campaign->deadline
                ? FormatHelper::formatDateTime($campaign->deadline, 'd/m/Y H:i')
                : __('messages.campaign_deadline_not_set'),
        ]);
        $lines[] = __('messages.campaign_product_budget', [
            'amount' => $campaign->max_budget
                ? FormatHelper::formatCurrency((int) $campaign->max_budget)
                : __('messages.campaign_product_budget_unlimited'),
        ]);
        if ($campaign->sponsor_name || $campaign->max_budget) {
            $lines[] = __('messages.campaign_sponsorship', [
                'sponsor' => $campaign->sponsor_name ?: __('messages.campaign_sponsor_not_set'),
                'amount' => $campaign->max_budget
                    ? FormatHelper::formatCurrency((int) $campaign->max_budget)
                    : __('messages.campaign_product_budget_unlimited'),
            ]);
        }
        if ($orderUrl !== null) {
            $lines[] = __('messages.campaign_order', ['url' => $orderUrl]);
        }

        return implode("\n", $lines);
    }
}
