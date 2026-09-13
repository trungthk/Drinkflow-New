<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Models\AdminAccount;
use App\Models\Campaign;
use App\Models\CampaignItem;
use App\Models\PaymentAccount;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $email = 'admin@example.test'): AdminAccount
    {
        return AdminAccount::create(['name' => 'Room Admin', 'email' => $email, 'password' => 'secret', 'role' => AdminRole::Admin, 'status' => 'active']);
    }

    private function roomFor(AdminAccount $admin, string $slug = 'admin-room'): Room
    {
        $room = Room::create(['name' => 'Admin Room', 'slug' => $slug, 'status' => 'active']);
        $admin->rooms()->attach($room);
        return $room;
    }

    public function test_admin_dashboard_is_limited_to_assigned_room(): void
    {
        $admin = $this->admin();
        $room = $this->roomFor($admin);
        $otherRoom = Room::create(['name' => 'Other', 'slug' => 'other-room', 'status' => 'active']);

        $this->actingAs($admin, 'admin')->getJson("/admin/{$room->id}/dashboard/data")->assertOk()->assertJsonPath('data.active_campaigns', 0);
        $this->actingAs($admin, 'admin')->getJson("/admin/{$otherRoom->id}/dashboard/data")->assertForbidden();
    }

    public function test_admin_can_activate_a_campaign_after_menu_and_payment_validation(): void
    {
        $admin = $this->admin('activate@example.test');
        $room = $this->roomFor($admin, 'activate-room');
        $account = PaymentAccount::create(['room_id' => $room->id, 'bank_code' => 'VCB', 'bank_name' => 'Vietcombank', 'account_number' => '123456', 'account_name' => 'DrinkFlow', 'status' => 'active']);
        $campaign = Campaign::create(['room_id' => $room->id, 'name' => 'Lunch', 'restaurant' => 'Cafe', 'payment_account_id' => $account->id, 'status' => 'draft']);
        CampaignItem::create(['campaign_id' => $campaign->id, 'name' => 'Oolong', 'normalized_name' => 'OOLONG', 'base_price' => 30000, 'status' => 'active']);

        $this->actingAs($admin, 'admin')->postJson("/admin/{$room->id}/campaigns/{$campaign->id}/activate")->assertOk()->assertJsonPath('data.status', 'active');
    }

    public function test_notification_channel_credentials_are_encrypted_and_masked(): void
    {
        $admin = $this->admin('notify@example.test');
        $room = $this->roomFor($admin, 'notify-room');
        $response = $this->actingAs($admin, 'admin')->postJson("/admin/{$room->id}/notification-channels", ['type' => 'slack', 'name' => 'Room Slack', 'status' => 'enabled', 'config' => ['webhook' => 'https://secret.example/hook']]);

        $response->assertCreated()->assertJsonPath('data.configured', true)->assertJsonMissing(['webhook' => 'https://secret.example/hook']);
        $this->assertDatabaseMissing('notification_channels', ['config_encrypted' => 'https://secret.example/hook']);
        $this->actingAs($admin, 'admin')->getJson("/admin/{$room->id}/notification-channels")->assertOk()->assertJsonMissing(['webhook' => 'https://secret.example/hook']);
    }

    public function test_room_settings_update_is_audited(): void
    {
        $admin = $this->admin('settings@example.test');
        $room = $this->roomFor($admin, 'settings-room');

        $this->actingAs($admin, 'admin')->patchJson("/admin/{$room->id}/settings", ['name' => 'Updated Room', 'language' => 'en', 'default_sponsor' => 'Company'])->assertOk()->assertJsonPath('data.name', 'Updated Room');
        $this->assertDatabaseHas('audit_logs', ['event' => 'room.settings_updated', 'room_id' => $room->id]);
        $this->assertDatabaseHas('room_settings', ['room_id' => $room->id, 'key' => 'default_sponsor', 'value' => 'Company']);
    }

    public function test_room_report_qualifies_order_columns_after_joining_campaigns(): void
    {
        $admin = $this->admin('reports@example.test');
        $room = $this->roomFor($admin, 'reports-room');
        Campaign::create(['room_id' => $room->id, 'name' => 'Lunch', 'restaurant' => 'Cafe', 'status' => 'closed']);

        $this->actingAs($admin, 'admin')->getJson("/admin/{$room->id}/reports?period=month")
            ->assertOk()
            ->assertJsonPath('data.popular_stores', []);
    }

    public function test_admin_dashboard_returns_weekly_trend_and_metrics(): void
    {
        $admin = $this->admin('trend@example.test');
        $room = $this->roomFor($admin, 'trend-room');

        $response = $this->actingAs($admin, 'admin')->getJson("/admin/{$room->id}/dashboard/data");
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'active_rooms',
                    'orders_today',
                    'outstanding_debts',
                    'weekly_trend',
                    'weekly_total_campaigns',
                    'weekly_total_spending',
                ]
            ]);

        $this->assertCount(7, $response->json('data.weekly_trend'));
    }

    public function test_admin_can_adjust_order_item_price_and_recalculate(): void
    {
        $admin = $this->admin('adjust@example.test');
        $room = $this->roomFor($admin, 'adjust-room');
        $campaign = Campaign::create(['room_id' => $room->id, 'name' => 'Tea Time', 'restaurant' => 'Highlands', 'status' => 'active']);
        $user = \App\Models\GlobalUser::create(['email' => 'member@example.test', 'name' => 'Test Member', 'normalized_name' => 'TEST MEMBER', 'status' => 'active']);
        $roomUser = \App\Models\RoomUser::create([
            'room_id' => $room->id,
            'global_user_id' => $user->id,
            'user_code' => 'MEM01',
            'display_name' => 'Member One',
            'normalized_name' => 'MEMBER ONE',
            'status' => 'active',
        ]);

        $order = \App\Models\Order::create([
            'room_id' => $room->id,
            'campaign_id' => $campaign->id,
            'room_user_id' => $roomUser->id,
            'subtotal' => 45000,
            'final_amount' => 45000,
            'status' => 'submitted',
        ]);

        $item = \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'item_name' => 'Trà Đào',
            'unit_price' => 45000,
            'quantity' => 1,
            'line_subtotal' => 45000,
        ]);

        $response = $this->actingAs($admin, 'admin')->patchJson("/admin/{$room->id}/orders/{$order->id}", [
            'items' => [
                ['id' => $item->id, 'unit_price' => 50000],
            ],
            'reason' => 'GrabFood tăng giá mùa mưa',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.subtotal', 50000)
            ->assertJsonPath('data.final_amount', 50000);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'order.price_adjusted',
            'room_id' => $room->id,
            'target_id' => $order->id,
        ]);
    }

    public function test_admin_password_recovery_otp_flow(): void
    {
        $admin = $this->admin('recovery@example.test');

        // Step 1: Request OTP
        $this->post('/admin/forgot-password', [
            'email' => 'recovery@example.test',
        ])->assertRedirect(route('admin.verify-otp.page'));

        $this->assertTrue(session()->has('admin_reset_otp'));
        $otp = session('admin_reset_otp');

        // Step 2: Verify Invalid OTP fails
        $this->post('/admin/verify-otp', [
            'otp' => '000000',
        ])->assertSessionHasErrors('otp');

        // Step 3: Verify Valid OTP succeeds
        $this->post('/admin/verify-otp', [
            'otp' => $otp,
        ])->assertRedirect(route('admin.reset-password.page'));

        $this->assertTrue(session('admin_reset_verified'));

        // Step 4: Reset Password
        $this->post('/admin/reset-password', [
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ])->assertRedirect(route('admin.login.page'));

        // Step 5: Login with new password
        session(['admin_captcha_answer' => '10']);
        $this->post('/admin/login', [
            'email' => 'recovery@example.test',
            'password' => 'NewSecurePassword123!',
            'captcha' => '10',
        ])->assertRedirect();
    }

    public function test_admin_can_retrieve_previous_campaign_menus(): void
    {
        $admin = $this->admin('prevmenu@example.test');
        $room = $this->roomFor($admin, 'prevmenu-room');

        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Past Lunch',
            'restaurant' => 'Phúc Long',
            'status' => 'closed',
        ]);

        CampaignItem::create([
            'campaign_id' => $campaign->id,
            'name' => 'Trà Sữa Phúc Long',
            'normalized_name' => 'TRA SUA PHUC LONG',
            'base_price' => 55000,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'admin')->getJson("/admin/{$room->id}/campaigns/previous-menus");
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Past Lunch')
            ->assertJsonPath('data.0.items.0.name', 'Trà Sữa Phúc Long');
    }

    public function test_admin_can_close_campaign_with_automatic_debt_creation(): void
    {
        $admin = $this->admin('closedebt@example.test');
        $room = $this->roomFor($admin, 'closedebt-room');

        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Friday Coffee',
            'restaurant' => 'Highlands',
            'status' => 'active',
        ]);

        $user = \App\Models\GlobalUser::create([
            'email' => 'memberdebt@example.test',
            'name' => 'Debt Member',
            'normalized_name' => 'DEBT MEMBER',
            'status' => 'active',
        ]);

        $roomUser = \App\Models\RoomUser::create([
            'room_id' => $room->id,
            'global_user_id' => $user->id,
            'user_code' => 'MEMDEBT',
            'display_name' => 'Debt Member',
            'normalized_name' => 'DEBT MEMBER',
            'status' => 'active',
        ]);

        \App\Models\Order::create([
            'room_id' => $room->id,
            'campaign_id' => $campaign->id,
            'room_user_id' => $roomUser->id,
            'subtotal' => 60000,
            'sponsor_amount' => 10000,
            'final_amount' => 50000,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($admin, 'admin')->postJson("/admin/{$room->id}/campaigns/{$campaign->id}/close", [
            'allow_debt' => true,
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'closed');

        $this->assertDatabaseHas('debts', [
            'campaign_id' => $campaign->id,
            'room_user_id' => $roomUser->id,
            'original_amount' => 50000,
            'remaining_amount' => 50000,
            'status' => 'unpaid',
        ]);
    }
}

