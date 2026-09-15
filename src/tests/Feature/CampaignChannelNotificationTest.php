<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Events\CampaignCancelled;
use App\Events\CampaignClosed;
use App\Events\CampaignCreated;
use App\Listeners\NotifyCampaignCancelled;
use App\Listeners\NotifyCampaignClosed;
use App\Listeners\NotifyCampaignCreated;
use App\Models\Campaign;
use App\Models\Room;
use App\Services\Notification\RoomNotificationChannelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CampaignChannelNotificationTest extends TestCase
{
    use RefreshDatabase;

    /** Verify every campaign lifecycle notification reaches enabled webhooks with the campaign payload. */
    public function test_created_closed_and_cancelled_campaigns_notify_enabled_webhook_channels(): void
    {
        Http::fake(['https://hooks.example.test/campaigns' => Http::response(['accepted' => true], 202)]);
        $room = Room::create(['name' => 'Marketing', 'slug' => 'marketing']);
        app(RoomNotificationChannelService::class)->save($room->id, [
            'type' => 'webhook',
            'name' => 'Campaign automation',
            'status' => 'enabled',
            'config' => ['webhook_url' => 'https://hooks.example.test/campaigns', 'secret_token' => 'channel-secret'],
        ]);
        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Friday coffee',
            'restaurant' => 'Cafe',
            'deadline' => now()->addHour(),
            'sponsor_name' => 'Marketing Fund',
            'max_budget' => 500000,
            'status' => CampaignStatus::Active,
        ])->load('room');

        app(NotifyCampaignCreated::class)->handle(new CampaignCreated($campaign));
        app(NotifyCampaignClosed::class)->handle(new CampaignClosed($campaign));
        app(NotifyCampaignCancelled::class)->handle(new CampaignCancelled($campaign));

        Http::assertSentCount(3);
        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://hooks.example.test/campaigns'
                && $request['campaign']['name'] === 'Friday coffee'
                && $request['campaign']['sponsor_name'] === 'Marketing Fund'
                && $request['campaign']['sponsorship_amount'] === 500000
                && $request->hasHeader('X-Webhook-Secret', 'channel-secret');
        });
        Http::assertSent(fn ($request): bool => $request['event'] === 'campaign.created' && filled($request['campaign']['deadline']) && filled($request['campaign']['order_url']));
        Http::assertSent(fn ($request): bool => $request['event'] === 'campaign.closed');
        Http::assertSent(fn ($request): bool => $request['event'] === 'campaign.cancelled');
    }
}
