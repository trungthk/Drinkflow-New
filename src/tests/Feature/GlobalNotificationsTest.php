<?php

namespace Tests\Feature;

use App\Models\GlobalUser;
use App\Models\UserNotification;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_accessing_notifications_redirects_to_home_or_referer(): void
    {
        $response = $this->get('/me/notifications');
        $response->assertRedirect('/');

        $refererResponse = $this->from(url('/terms'))->get('/me/notifications');
        $refererResponse->assertRedirect(url('/terms'));
    }

    public function test_authenticated_user_can_view_notifications_page(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
            'status' => 'active',
        ]);

        UserNotification::create([
            'global_user_id' => $user->id,
            'type' => 'campaign.created',
            'title' => 'Chiến dịch mới mở: Friday Coffee',
            'body' => 'Quán Highlands Coffee đang mở gom đến 10:30',
        ]);

        $response = $this->actingAs($user, 'web')->get('/me/notifications');

        $response->assertOk();
        $response->assertSee('Thông báo &amp; Cập nhật', false);
        $response->assertSee('Tất cả');
        $response->assertSee('Chưa đọc');
        $response->assertSee('Chiến dịch mới mở: Friday Coffee');
    }

    public function test_user_can_filter_notifications_by_tab(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
            'status' => 'active',
        ]);

        UserNotification::create([
            'global_user_id' => $user->id,
            'type' => 'security.alert',
            'title' => 'Đăng nhập từ thiết bị mới',
            'body' => 'Phiên đăng nhập mới từ Chrome trên Windows 11',
            'read_at' => now(),
        ]);

        $response = $this->actingAs($user, 'web')->get('/me/notifications?tab=security');
        $response->assertOk();
        $response->assertSee('Đăng nhập từ thiết bị mới');

        $responseUnread = $this->actingAs($user, 'web')->get('/me/notifications?tab=unread');
        $responseUnread->assertOk();
        $responseUnread->assertDontSee('Đăng nhập từ thiết bị mới');
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
            'status' => 'active',
        ]);

        UserNotification::create([
            'global_user_id' => $user->id,
            'type' => 'campaign.created',
            'title' => 'Thông báo 1',
            'body' => 'Nội dung 1',
        ]);

        UserNotification::create([
            'global_user_id' => $user->id,
            'type' => 'order.status',
            'title' => 'Thông báo 2',
            'body' => 'Nội dung 2',
        ]);

        $this->assertEquals(2, $user->notifications()->whereNull('read_at')->count());

        $response = $this->withoutMiddleware(ValidateCsrfToken::class)
            ->actingAs($user, 'web')
            ->postJson('/me/notifications/read-all');

        $response->assertOk()->assertJsonPath('marked_count', 2);

        $this->assertEquals(0, $user->notifications()->whereNull('read_at')->count());
    }

    public function test_json_api_still_returns_json(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'web')->getJson('/notifications');
        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    /**
     * Mark one owned notification as read and return the authoritative unread count.
     *
     * @return void
     */
    public function test_user_can_mark_one_notification_as_read(): void
    {
        $user = GlobalUser::create([
            'name' => 'Notification User', 'normalized_name' => 'NOTIFICATION USER',
            'email' => 'notification-single@company.com', 'status' => 'active',
        ]);
        $first = UserNotification::create([
            'global_user_id' => $user->id, 'type' => 'campaign.created',
            'title' => 'First', 'body' => 'First body',
        ]);
        UserNotification::create([
            'global_user_id' => $user->id, 'type' => 'order.status',
            'title' => 'Second', 'body' => 'Second body',
        ]);

        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->actingAs($user, 'web')
            ->patchJson(route('user.notifications.read', $first))
            ->assertOk()
            ->assertJsonPath('data.id', $first->id)
            ->assertJsonPath('unread_count', 1);

        $this->assertNotNull($first->fresh()->read_at);
    }

    /**
     * Prevent one user from reading another user's notification.
     *
     * @return void
     */
    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $owner = GlobalUser::create([
            'name' => 'Owner', 'normalized_name' => 'OWNER',
            'email' => 'notification-owner@company.com', 'status' => 'active',
        ]);
        $attacker = GlobalUser::create([
            'name' => 'Attacker', 'normalized_name' => 'ATTACKER',
            'email' => 'notification-attacker@company.com', 'status' => 'active',
        ]);
        $notification = UserNotification::create([
            'global_user_id' => $owner->id, 'type' => 'campaign.created',
            'title' => 'Private', 'body' => 'Private body',
        ]);

        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->actingAs($attacker, 'web')
            ->patchJson(route('user.notifications.read', $notification))
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    /**
     * Ensure notification automatically populates body when not explicitly provided.
     */
    public function test_notification_auto_generates_body_when_missing(): void
    {
        $user = GlobalUser::create([
            'name' => 'Auto Body User',
            'normalized_name' => 'AUTO BODY USER',
            'email' => 'autobody@company.com',
            'status' => 'active',
        ]);

        $notif1 = UserNotification::create([
            'global_user_id' => $user->id,
            'type' => 'order.created',
            'title' => 'Order created',
            'data' => ['order_id' => 123, 'order_code' => 'ORD-20260916-TEST'],
        ]);

        $notif2 = UserNotification::create([
            'global_user_id' => $user->id,
            'type' => 'campaign.created',
            'title' => 'Campaign started',
        ]);

        $this->assertNotEmpty($notif1->fresh()->body);
        $this->assertStringContainsString('ORD-20260916-TEST', $notif1->fresh()->body);

        $this->assertNotEmpty($notif2->fresh()->body);
        $this->assertStringContainsString('Chiến dịch đặt món mới', $notif2->fresh()->body);
    }
}
