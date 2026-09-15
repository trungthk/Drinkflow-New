<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Models\Campaign;

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
            'campaign.created' => 'Campaign mới',
            'campaign.cancelled' => 'Campaign đã hủy',
            default => 'Campaign đã đóng',
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
                'deadline' => $campaign->deadline?->toIso8601String(),
                'sponsor_name' => $campaign->sponsor_name,
                'sponsorship_amount' => $campaign->max_budget,
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
        $lines = [$title, 'Tên: '.$campaign->name];
        $lines[] = 'Thời hạn: '.($campaign->deadline?->format('d/m/Y H:i') ?? 'Chưa thiết lập');
        $lines[] = 'Tài trợ: '.($campaign->sponsor_name ?? 'Không có').($campaign->max_budget ? ' · '.number_format((int) $campaign->max_budget, 0, ',', '.').'₫' : '');
        if ($orderUrl !== null) {
            $lines[] = 'Đặt món: '.$orderUrl;
        }

        return implode("\n", $lines);
    }
}
