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
}
