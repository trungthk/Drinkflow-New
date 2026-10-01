<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Enums\NotificationType;
use App\Enums\OrderStatus;
use App\Models\Campaign;
use App\Models\Debt;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\Room;
use App\Models\RoomUser;
use App\Models\UserNotification;
use App\Services\Notification\NotificationPresentationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPresentationTest extends TestCase
{
    use RefreshDatabase;

    private GlobalUser $user;

    private Room $room;

    private RoomUser $roomUser;

    private Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('vi');
        $this->room = Room::create(['name' => 'Notify Room', 'slug' => 'notify-room', 'status' => 'active']);
        $this->user = GlobalUser::create(['name' => 'Member', 'email' => 'notify-member@example.test', 'status' => 'active']);
        $this->roomUser = RoomUser::create([
            'room_id' => $this->room->id,
            'global_user_id' => $this->user->id,
            'display_name' => 'Member',
            'status' => 'active',
        ]);
        $this->campaign = Campaign::create([
            'room_id' => $this->room->id,
            'name' => 'Trà chiều',
            'restaurant' => 'Cafe',
            'status' => CampaignStatus::Active,
        ]);
    }

    private function notify(string $type, array $data, string $title = 'Stored title', ?string $body = 'Stored body'): UserNotification
    {
        return UserNotification::create([
            'global_user_id' => $this->user->id,
            'room_user_id' => $this->roomUser->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }

    public function test_order_created_shows_order_code_and_links_to_order(): void
    {
        $order = Order::create([
            'room_id' => $this->room->id,
            'campaign_id' => $this->campaign->id,
            'room_user_id' => $this->roomUser->id,
            'subtotal' => 30000,
            'final_amount' => 30000,
            'status' => OrderStatus::Submitted,
        ]);
        $notification = $this->notify(NotificationType::OrderCreated->value, ['order_id' => $order->id, 'room_id' => $this->room->id]);

        $presented = app(NotificationPresentationService::class)->present($notification);

        $this->assertSame(__('messages.order_created_body', ['order_code' => $order->code]), $presented['body']);
        $this->assertStringNotContainsString('#' . $order->id, $presented['body']);
        $this->assertSame(route('user.orders.page', [$this->room, $order]), $presented['link']);
    }

    public function test_admin_broadcast_shows_the_content_sent_by_the_admin(): void
    {
        $notification = $this->notify(NotificationType::CampaignCreated->value, ['room_id' => $this->room->id, 'broadcast' => true], 'Họp 3h', 'Mọi người vào phòng họp A');

        $presented = app(NotificationPresentationService::class)->present($notification);

        $this->assertSame('Họp 3h', $presented['title']);
        $this->assertSame('Mọi người vào phòng họp A', $presented['body']);
    }

    /** "New campaign #CODE" opens the campaign info page. */
    public function test_new_campaign_title_has_code_and_links_to_campaign_info(): void
    {
        $notification = $this->notify(NotificationType::CampaignCreated->value, ['campaign_id' => $this->campaign->id, 'room_id' => $this->room->id]);

        $presented = app(NotificationPresentationService::class)->present($notification);

        $this->assertSame(__('messages.campaign_created_title', ['code' => $this->campaign->code]), $presented['title']);
        $this->assertStringContainsString('#' . $this->campaign->code, $presented['title']);
        $this->assertSame(route('user.campaigns.index', $this->room), $presented['link']);
    }

    /** "Campaign #CODE closed" carries the campaign code. */
    public function test_campaign_closed_title_has_code(): void
    {
        $notification = $this->notify(NotificationType::CampaignClosed->value, ['campaign_id' => $this->campaign->id, 'room_id' => $this->room->id]);

        $presented = app(NotificationPresentationService::class)->present($notification);

        $this->assertSame('Chiến dịch #' . $this->campaign->code . ' đã đóng', $presented['title']);
    }

    /** "Campaign #CODE ordering locked" opens the campaign info page; the reopened notice keeps its stored title. */
    public function test_ordering_locked_title_has_code_and_links_to_campaign_info(): void
    {
        $service = app(NotificationPresentationService::class);
        $locked = $this->notify(NotificationType::CampaignUpdated->value, ['campaign_id' => $this->campaign->id, 'room_id' => $this->room->id, 'ordering_locked' => true]);
        $unlocked = $this->notify(NotificationType::CampaignUpdated->value, ['campaign_id' => $this->campaign->id, 'room_id' => $this->room->id, 'ordering_locked' => false], 'Reopened');

        $this->assertSame(__('messages.campaign_ordering_locked_title', ['code' => $this->campaign->code]), $service->present($locked)['title']);
        $this->assertSame(route('user.campaigns.index', $this->room), $service->present($locked)['link']);
        $this->assertSame('Reopened', $service->present($unlocked)['title']);
    }

    /** "Items delivered" opens the member's "My orders" page. */
    public function test_items_delivered_links_to_my_orders(): void
    {
        $notification = $this->notify(NotificationType::CampaignDelivering->value, ['campaign_id' => $this->campaign->id]);

        $this->assertSame(route('user.orders.index', $this->room), app(NotificationPresentationService::class)->present($notification)['link']);
    }

    public function test_payment_reminder_title_contains_debt_code_and_opens_debt_payment(): void
    {
        $debt = Debt::create([
            'room_id' => $this->room->id,
            'campaign_id' => $this->campaign->id,
            'room_user_id' => $this->roomUser->id,
            'original_amount' => 30000,
            'remaining_amount' => 30000,
            'status' => 'unpaid',
        ]);
        // Even when an order code is present, the reminder refers to the debt.
        $notification = $this->notify(NotificationType::PaymentReminder->value, ['debt_id' => $debt->id, 'order_code' => 'ORD-1', 'campaign_id' => $this->campaign->id]);

        $presented = app(NotificationPresentationService::class)->present($notification);

        $this->assertSame('Vui lòng thanh toán cho công nợ #' . $debt->code . ' của bạn', $presented['title']);
        $this->assertSame(route('user.debts.index', ['room' => $this->room, 'debt' => $debt->code]), $presented['link']);
    }

    /** The room notification center shows presented titles and exposes each item's link for click-through. */
    public function test_room_notification_page_items_carry_link(): void
    {
        $this->notify(NotificationType::CampaignDelivering->value, ['campaign_id' => $this->campaign->id]);

        $this->actingAs($this->user, 'web')->get(route('user.rooms.notifications', $this->room))
            ->assertOk()
            ->assertSee('openNotification(item)', false)
            // Js::from() escapes "/" as "\\\/" inside JSON.parse('...').
            ->assertSee(str_replace('/', '\\\\\\/', route('user.orders.index', $this->room)), false);
    }

    public function test_header_items_expose_read_url_for_click_to_read(): void
    {
        $notification = $this->notify(NotificationType::AdminBroadcast->value, ['room_id' => $this->room->id, 'broadcast' => true]);

        $this->actingAs($this->user, 'web')->get('/me/notifications')
            ->assertOk()
            ->assertSee('data-header-notification-id="' . $notification->id . '"', false)
            ->assertSee('data-read-url="' . route('user.notifications.read', $notification) . '"', false)
            ->assertSee('data-unread="1"', false);
    }
}
