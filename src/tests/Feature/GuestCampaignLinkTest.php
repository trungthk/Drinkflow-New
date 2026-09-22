<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Room;
use App\Services\Notification\RoomNotificationChannelDispatcher;
use App\Services\Notification\CampaignNotificationPayloadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestCampaignLinkTest extends TestCase
{
    use RefreshDatabase;

    /** Verify a guest opening a campaign link lands on the home page with the register modal and message. */
    public function test_guest_opening_campaign_link_is_sent_home_with_register_modal(): void
    {
        $room = Room::create(['name' => 'Marketing', 'slug' => 'marketing']);
        $message = __('public.auth_modal.require_register');

        $response = $this->get("/rooms/{$room->slug}/campaigns");

        $response->assertRedirect('/');
        $response->assertSessionHas('auth_notice', $message);
        $response->assertSessionHas('url.intended', url("/rooms/{$room->slug}/campaigns"));

        $this->followingRedirects()->get("/rooms/{$room->slug}/campaigns")
            ->assertOk()
            ->assertSee($message)
            ->assertSee('data-auto-open="true"', false);
    }

    /** Verify the announcement states "no sponsor" plainly, without amounts or descriptions. */
    public function test_campaign_announcement_shows_plain_none_when_no_sponsor(): void
    {
        $room = Room::create(['name' => 'Marketing', 'slug' => 'marketing']);
        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Friday coffee',
            'restaurant' => 'Cafe',
            'max_budget' => 70000,
            'status' => CampaignStatus::Active,
        ])->load('room');

        $message = app(CampaignNotificationPayloadService::class)->make($campaign, 'campaign.created')['message'];

        $this->assertStringContainsString(__('messages.campaign_sponsorship', [
            'sponsor' => __('messages.campaign_sponsor_not_set'),
            'amount' => '',
        ]), $message);
        $this->assertStringNotContainsString(__('messages.campaign_sponsor_not_set') . '70', $message);

        $campaign->update(['sponsor_name' => 'Team Lead']);
        $withSponsor = app(CampaignNotificationPayloadService::class)->make($campaign->fresh('room'), 'campaign.created')['message'];
        $this->assertStringContainsString('Team Lead', $withSponsor);
    }

    /** Verify the new-campaign announcement carries the room registration link for gateways. */
    public function test_campaign_announcement_includes_room_register_link(): void
    {
        $room = Room::create(['name' => 'Marketing', 'slug' => 'marketing']);
        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Friday coffee',
            'restaurant' => 'Cafe',
            'status' => CampaignStatus::Active,
        ])->load('room');

        $service = app(CampaignNotificationPayloadService::class);
        $payload = $service->make($campaign, 'campaign.created');

        $registerUrl = url('/rooms/marketing');
        $this->assertSame($registerUrl, $payload['campaign']['register_url']);
        $this->assertStringContainsString(__('messages.campaign_register', ['url' => $registerUrl]), $payload['message']);
        $this->assertStringContainsString($registerUrl, app(RoomNotificationChannelDispatcher::class)->formatTelegramMessage($payload));

        $closed = $service->make($campaign, 'campaign.closed');
        $this->assertNull($closed['campaign']['register_url']);
        $this->assertStringNotContainsString(__('messages.campaign_register', ['url' => $registerUrl]), $closed['message']);
    }

    /** Closed campaign gateway copy uses the sponsor-specific reminder and signed order check link. */
    public function test_closed_campaign_gateway_reminder_depends_on_sponsorship(): void
    {
        $room = Room::create(['name' => 'Marketing', 'slug' => 'marketing']);
        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Friday coffee',
            'restaurant' => 'Cafe',
            'sponsor_type' => Campaign::SPONSOR_TYPE_NONE,
            'status' => CampaignStatus::Closed,
        ])->load('room');

        $service = app(CampaignNotificationPayloadService::class);
        $withoutSponsor = $service->make($campaign, 'campaign.closed');
        $this->assertStringContainsString(
            __('messages.campaign_closed_body').' => '.$withoutSponsor['campaign']['order_check_url'],
            $withoutSponsor['message']
        );
        $this->assertStringNotContainsString(__('messages.campaign_closed_sponsored_body'), $withoutSponsor['message']);

        $campaign->update(['sponsor_type' => Campaign::SPONSOR_TYPE_FULL, 'sponsor_name' => 'Team Lead']);
        $withSponsor = $service->make($campaign->fresh('room'), 'campaign.closed');
        $this->assertStringContainsString(
            __('messages.campaign_closed_sponsored_body').' => '.$withSponsor['campaign']['order_check_url'],
            $withSponsor['message']
        );
        $this->assertStringNotContainsString(__('messages.campaign_closed_body'), $withSponsor['message']);
        $this->assertStringContainsString(
            __('messages.campaign_closed_sponsored_body'),
            app(RoomNotificationChannelDispatcher::class)->formatTelegramMessage($withSponsor)
        );
    }
}
