<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Enums\NotificationType;
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
            NotificationType::CampaignCreated->value => __('messages.campaign_created_title'),
            NotificationType::CampaignCancelled->value => __('messages.campaign_cancelled_title'),
            NotificationType::CampaignUpdated->value => __('messages.campaign_updated_title'),
            NotificationType::CampaignDelivering->value => __('messages.campaign_delivering_title'),
            default => __('messages.campaign_closed_title'),
        };
        $orderUrl = in_array($event, [NotificationType::CampaignCreated->value, NotificationType::CampaignUpdated->value], true) && $room !== null
            ? route('user.campaigns.index', $room)
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
            'message' => $this->message($title, $campaign, $orderUrl, $event),
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
    private function message(string $title, Campaign $campaign, ?string $orderUrl, string $event = 'campaign.created'): string
    {
        $lines = [$title, __('messages.campaign_name', ['name' => $campaign->name])];
        if (! empty($campaign->restaurant)) {
            $lines[] = __('messages.campaign_restaurant', ['restaurant' => $campaign->restaurant]);
        }

        if ($event === NotificationType::CampaignCreated->value) {
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
        } elseif ($event === NotificationType::CampaignUpdated->value) {
            $lines[] = __('messages.campaign_updated_body');
            if ($campaign->deadline) {
                $lines[] = __('messages.campaign_deadline', [
                    'date' => FormatHelper::formatDateTime($campaign->deadline, 'd/m/Y H:i'),
                ]);
            }
            if ($orderUrl !== null) {
                $lines[] = __('messages.campaign_order', ['url' => $orderUrl]);
            }
        } elseif ($event === NotificationType::CampaignClosed->value) {
            $lines[] = __('messages.campaign_closed_body');
        } elseif ($event === NotificationType::CampaignCancelled->value) {
            $lines[] = __('messages.campaign_cancelled_body');
        } elseif ($event === NotificationType::CampaignDelivering->value) {
            $lines[] = __('messages.campaign_delivering_body', [
                'restaurant' => $campaign->restaurant,
                'code' => $campaign->code,
            ]);
        }

        return implode("\n", $lines);
    }
}
