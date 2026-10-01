<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Enums\GlobalUserStatus;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Services\Notification\RoomNotificationChannelDispatcher;
use App\Services\Notification\CampaignNotificationPayloadService;
use App\Support\Helpers\FormatHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestCampaignLinkTest extends TestCase
{
    use RefreshDatabase;

    /** Verify a guest opening a campaign link lands on the home page with the login modal and message. */
    public function test_guest_opening_campaign_link_is_sent_home_with_login_modal(): void
    {
        $room = Room::create(['name' => 'Marketing', 'slug' => 'marketing']);
        $message = __('public.auth_modal.require_login_room', ['room' => $room->name]);

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

    /** The new-campaign announcement only carries the order link (no separate room registration link). */
    public function test_campaign_announcement_only_includes_order_link(): void
    {
        $room = Room::create(['name' => 'Marketing', 'slug' => 'marketing']);
        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Friday coffee',
            'restaurant' => 'Cafe',
            'status' => CampaignStatus::Active,
        ])->load('room');

        $payload = app(CampaignNotificationPayloadService::class)->make($campaign, 'campaign.created');
        $orderUrl = route('user.campaigns.index', $room);

        $this->assertSame($orderUrl, $payload['campaign']['order_url']);
        $this->assertArrayNotHasKey('register_url', $payload['campaign']);
        $this->assertStringContainsString(__('messages.campaign_order', ['url' => $orderUrl]), $payload['message']);

        $telegram = app(RoomNotificationChannelDispatcher::class)->formatTelegramMessage($payload);
        $this->assertStringContainsString($orderUrl, $telegram);
        $this->assertSame(1, substr_count($telegram, 'href='));
    }

    /**
     * Order link flow: a guest logs in from the modal, is sent to the room join page, and after
     * joining lands on the order page.
     */
    public function test_order_link_sends_new_member_through_login_and_join_to_order_page(): void
    {
        $room = Room::create(['name' => 'Marketing', 'slug' => 'marketing-join-flow', 'status' => 'active']);
        $orderUrl = route('user.campaigns.index', $room);

        $this->get($orderUrl)->assertRedirect('/')->assertSessionHas('url.intended', $orderUrl);

        // After Google login the user is sent back to the intended order link.
        $user = GlobalUser::create(['name' => 'New Member', 'email' => 'new-member@example.test', 'status' => GlobalUserStatus::Active]);
        $this->actingAs($user, 'web')->get($orderUrl)->assertRedirect(route('user.rooms.join.show', $room->slug));

        $this->actingAs($user, 'web')->post(route('user.rooms.join', $room->slug))->assertRedirect($orderUrl);
        $this->assertTrue($user->roomUsers()->where('room_id', $room->id)->exists());

        // Joining straight from the room page (no pending order link) still lands on the dashboard.
        $other = Room::create(['name' => 'Sales', 'slug' => 'sales-join-flow', 'status' => 'active']);
        $this->actingAs($user, 'web')->post(route('user.rooms.join', $other->slug))->assertRedirect(route('user.dashboard', $other->slug));
    }

    /** Closed campaign gateway copy shows the deadline and no longer carries the sponsor reminder or order-check link. */
    public function test_closed_campaign_gateway_message_shows_deadline_without_sponsor_reminder(): void
    {
        $room = Room::create(['name' => 'Marketing', 'slug' => 'marketing']);
        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Friday coffee',
            'restaurant' => 'Cafe',
            'sponsor_type' => Campaign::SPONSOR_TYPE_FULL,
            'sponsor_name' => 'Team Lead',
            'deadline' => now()->setDate(2026, 10, 1)->setTime(15, 30),
            'status' => CampaignStatus::Closed,
        ])->load('room');

        $payload = app(CampaignNotificationPayloadService::class)->make($campaign, 'campaign.closed');

        $this->assertStringContainsString(
            __('messages.campaign_deadline', ['date' => FormatHelper::formatDateTime($campaign->deadline, 'd/m/Y H:i')]),
            $payload['message']
        );
        $this->assertStringNotContainsString($payload['campaign']['order_check_url'], $payload['message']);

        // Without a deadline the line still appears with the "not set" fallback.
        $campaign->update(['deadline' => null]);
        $withoutDeadline = app(CampaignNotificationPayloadService::class)->make($campaign->fresh('room'), 'campaign.closed');
        $this->assertStringContainsString(
            __('messages.campaign_deadline', ['date' => __('messages.campaign_deadline_not_set')]),
            $withoutDeadline['message']
        );
    }

    /** Closed campaign gateway copy links to the debts page with the campaign code to open its payment modal. */
    public function test_closed_campaign_gateway_message_links_to_campaign_debt_payment(): void
    {
        $room = Room::create(['name' => 'Marketing', 'slug' => 'marketing-payment-link']);
        $campaign = Campaign::create([
            'room_id' => $room->id, 'name' => 'Friday coffee', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Closed,
        ])->load('room');

        $payload = app(CampaignNotificationPayloadService::class)->make($campaign, 'campaign.closed');
        $paymentUrl = route('user.debts.index', ['room' => $room, 'campaign' => $campaign->code]);

        $this->assertSame($paymentUrl, $payload['campaign']['payment_url']);
        $this->assertStringContainsString(__('messages.campaign_payment', ['url' => $paymentUrl]), $payload['message']);

        $dispatcher = app(RoomNotificationChannelDispatcher::class);
        $this->assertStringContainsString($paymentUrl, $dispatcher->formatChatworkMessage($payload));
        $this->assertStringContainsString($paymentUrl, $dispatcher->formatSlackMessage($payload));
        $this->assertStringContainsString(htmlspecialchars($paymentUrl, ENT_QUOTES), $dispatcher->formatTelegramMessage($payload));
    }

    /** The "items delivered" gateway message carries the signed order-check link; other events do not get a payment link. */
    public function test_delivering_gateway_message_links_to_order_check(): void
    {
        $room = Room::create(['name' => 'Marketing', 'slug' => 'marketing-delivered-link']);
        $campaign = Campaign::create([
            'room_id' => $room->id, 'name' => 'Friday coffee', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Closed,
        ])->load('room');

        $payload = app(CampaignNotificationPayloadService::class)->make($campaign, 'campaign.delivering');

        $this->assertNotNull($payload['campaign']['order_check_url']);
        $this->assertNull($payload['campaign']['payment_url']);
        $this->assertStringContainsString(
            __('messages.campaign_deadline', ['date' => __('messages.campaign_deadline_not_set')]),
            $payload['message']
        );
        $this->assertStringContainsString(
            __('messages.campaign_order_check', ['url' => $payload['campaign']['order_check_url']]),
            $payload['message']
        );
    }
}
