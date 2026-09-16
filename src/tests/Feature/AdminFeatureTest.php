<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\CampaignStatus;
use App\Models\AdminAccount;
use App\Models\Campaign;
use App\Models\CampaignItem;
use App\Models\PaymentAccount;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $email = 'admin@example.test'): AdminAccount
    {
        return AdminAccount::create(['name' => 'Room Admin', 'email' => $email, 'password' => Hash::make('secret'), 'role' => AdminRole::Admin, 'status' => 'active']);
    }

    private function roomFor(AdminAccount $admin, string $slug = 'admin-room'): Room
    {
        $room = Room::create(['name' => 'Admin Room', 'slug' => $slug, 'status' => 'active']);
        $admin->rooms()->attach($room);
        return $room;
    }

    public function test_admin_landing_renders_rooms_selection_page_with_active_campaigns(): void
    {
        $admin = $this->admin('multiroom@example.test');
        $room1 = $this->roomFor($admin, 'room-one');
        $room2 = $this->roomFor($admin, 'room-two');

        Campaign::create([
            'room_id' => $room1->id,
            'name' => 'Live Coffee',
            'restaurant' => 'Highlands',
            'status' => CampaignStatus::Active,
            'started_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin');
        $response->assertOk()
            ->assertSee(__('admin.select_room_heading'))
            ->assertDontSee(__('admin.switch_to_rooms'))
            ->assertDontSee(__('admin.badge_idle_ready'))
            ->assertDontSee(__('admin.room_manager_role'))
            ->assertDontSee(__('admin.metric_fund_limit'))
            ->assertSee('ROOM-ONE')
            ->assertSee('ROOM-TWO')
            ->assertSee('Live Coffee');
    }

    public function test_admin_can_view_and_update_own_profile(): void
    {
        $admin = $this->admin('profile@example.test');
        $this->roomFor($admin, 'profile-room');

        $this->actingAs($admin, 'admin')->get('/admin/profile')
            ->assertOk()
            ->assertViewIs('admin.profile')
            ->assertSee($admin->email)
            ->assertSee('/admin/profile')
            ->assertDontSee(__('admin.room_manager_role'))
            ->assertDontSee(__('admin.active_sessions'))
            ->assertSee('type="submit"', false);

        $this->actingAs($admin, 'admin')->patch('/admin/profile', [
            'name' => 'Updated Admin',
            'phone' => '0900000000',
            'department' => 'Operations',
        ])->assertRedirect('/admin/profile');

        $this->actingAs($admin->fresh(), 'admin')->patch('/admin/profile/two-factor', [
            'current_password' => 'secret',
            'two_factor_enabled' => true,
        ])->assertRedirect('/admin/profile');

        $this->assertDatabaseHas('admin_accounts', [
            'id' => $admin->id,
            'name' => 'Updated Admin',
            'phone' => '0900000000',
            'department' => 'Operations',
            'two_factor_enabled' => true,
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'admin.profile_updated', 'target_id' => $admin->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'admin.two_factor_updated', 'target_id' => $admin->id]);
    }

    /**
     * Campaign listing searches on the backend and renders the persisted order window.
     *
     * @return void
     */
    public function test_campaign_list_uses_backend_search_and_campaign_timestamps(): void
    {
        $admin = $this->admin('campaign-filter@example.test');
        $room = $this->roomFor($admin, 'campaign-filter-room');
        $startedAt = now()->setDate(2026, 9, 16)->setTime(9, 15, 0);
        $deadline = now()->setDate(2026, 9, 16)->setTime(10, 30, 0);
        Campaign::create([
            'room_id' => $room->id, 'name' => 'Highlands Morning', 'restaurant' => 'Highlands',
            'status' => 'active', 'started_at' => $startedAt, 'deadline' => $deadline,
        ]);
        Campaign::create([
            'room_id' => $room->id, 'name' => 'Other Campaign', 'restaurant' => 'Other Store',
            'status' => 'closed', 'started_at' => $startedAt->copy()->subDay(),
            'deadline' => $deadline->copy()->subDay(),
        ]);

        $response = $this->actingAs($admin, 'admin')->withSession(['locale' => 'en'])
            ->get(route('admin.campaigns.page', ['room' => $room->slug, 'search' => 'highlands']));

        $response->assertOk()
            ->assertSee('Highlands Morning')
            ->assertDontSee('Other Campaign')
            ->assertSee('From: '.$startedAt->format('H:i d/m/Y'))
            ->assertSee('Until: '.$deadline->format('H:i d/m/Y'));
    }

    public function test_guest_cannot_access_admin_profile(): void
    {
        $this->get('/admin/profile')->assertRedirect(route('admin.login.page'));
    }

    public function test_admin_login_updates_last_login_at(): void
    {
        config()->set('captcha.disable', true);
        $admin = $this->admin('last-login@example.test');
        $admin->update(['password' => Hash::make('CorrectPassword123!')]);

        $this->assertTrue(Hash::check('CorrectPassword123!', (string) $admin->password));

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'CorrectPassword123!',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertNotNull($admin->fresh()->last_login_at);
        $this->assertDatabaseHas('audit_logs', [
            'actor_type' => 'admin',
            'actor_id' => $admin->id,
            'event' => 'admin.logged_in',
            'target_type' => 'admin',
            'target_id' => $admin->id,
        ]);
    }

    /**
     * Ensure a successful first factor shows only the Google Workspace challenge.
     *
     * @return void
     */
    public function test_two_factor_admin_login_hides_first_factor_fields(): void
    {
        config()->set('captcha.disable', true);
        $admin = $this->admin('two-factor-login@example.test');
        $admin->update([
            'password' => Hash::make('CorrectPassword123!'),
            'two_factor_enabled' => true,
        ]);

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'CorrectPassword123!',
            'remember' => true,
        ])->assertRedirect(route('admin.login.page'))
            ->assertSessionHas('admin_google_2fa_admin_id', $admin->id)
            ->assertSessionHas('admin_google_2fa_remember', true);

        $this->assertGuest('admin');

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee(__('admin.sign_in_google_workspace'))
            ->assertSee(route('admin.login.two-factor.cancel'))
            ->assertDontSee('id="admin-email"', false)
            ->assertDontSee('id="admin-password"', false)
            ->assertDontSee('name="remember"', false);
    }

    /**
     * Ensure cancelling the Workspace challenge clears its session and restores manual login.
     *
     * @return void
     */
    public function test_admin_can_cancel_two_factor_workspace_login(): void
    {
        $admin = $this->admin('cancel-two-factor@example.test');

        $response = $this->withSession([
            'admin_google_2fa_admin_id' => $admin->id,
            'admin_google_2fa_remember' => true,
            'google_oauth_state' => 'pending-state',
            'google_oauth_login_source' => url('/admin/login'),
        ])->post('/admin/login/two-factor/cancel');

        $response->assertRedirect(route('admin.login.page'))
            ->assertSessionMissing('admin_google_2fa_admin_id')
            ->assertSessionMissing('admin_google_2fa_remember')
            ->assertSessionMissing('google_oauth_state')
            ->assertSessionMissing('google_oauth_login_source');

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('id="admin-email"', false)
            ->assertSee('id="admin-password"', false)
            ->assertSee('name="remember"', false)
            ->assertDontSee(__('admin.sign_in_google_workspace'));
    }

    public function test_admin_dashboard_is_limited_to_assigned_room(): void
    {
        $admin = $this->admin();
        $room = $this->roomFor($admin);
        $otherRoom = Room::create(['name' => 'Other', 'slug' => 'other-room', 'status' => 'active']);

        $this->actingAs($admin, 'admin')->get("/admin/{$room->slug}/dashboard")
            ->assertOk()
            ->assertSee('/lang/en')
            ->assertSee('/lang/ja');

        $this->actingAs($admin, 'admin')->getJson("/admin/{$room->id}/dashboard/data")->assertOk()->assertJsonPath('data.active_campaigns', 0);
        $this->actingAs($admin, 'admin')->getJson("/admin/{$otherRoom->id}/dashboard/data")->assertForbidden();
    }

    public function test_admin_can_create_payment_account(): void
    {
        $admin = $this->admin('payments-create@example.test');
        $room = $this->roomFor($admin, 'payments-create-room');

        $this->actingAs($admin, 'admin')->postJson("/admin/{$room->slug}/payment-accounts", [
            'bank_code' => 'VCB',
            'bank_name' => 'Vietcombank',
            'account_number' => '0123456789',
            'account_name' => 'DRINKFLOW',
            'is_default' => true,
            'status' => 'active',
        ])->assertCreated()->assertJsonPath('data.bank_code', 'VCB');

        $this->assertDatabaseHas('payment_accounts', ['room_id' => $room->id, 'account_number' => '0123456789']);
    }

    public function test_setting_a_default_payment_account_unsets_other_room_accounts_and_qr_is_local(): void
    {
        $admin = $this->admin('payments-default@example.test');
        $room = $this->roomFor($admin, 'payments-default-room');

        $first = PaymentAccount::create([
            'room_id' => $room->id,
            'bank_code' => 'VCB',
            'bank_name' => 'Vietcombank',
            'account_number' => '0123456789',
            'account_name' => 'DRINKFLOW',
            'is_default' => true,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'admin')->postJson("/admin/{$room->slug}/payment-accounts", [
            'bank_code' => 'MB',
            'bank_name' => 'MBBank',
            'account_number' => '0987654321',
            'account_name' => 'DRINKFLOW TWO',
            'is_default' => true,
            'status' => 'active',
        ]);

        $response->assertCreated();
        $secondId = $response->json('data.id');

        $this->assertDatabaseHas('payment_accounts', ['id' => $first->id, 'is_default' => false]);
        $this->assertDatabaseHas('payment_accounts', ['id' => $secondId, 'is_default' => true]);

        $this->actingAs($admin, 'admin')->getJson("/admin/{$room->slug}/payment-accounts/{$secondId}/qr")
            ->assertOk()
            ->assertJsonPath('data.payload', "DRINKFLOW-PAYMENT\nBANK:MBBank\nBANK_CODE:MB\nACCOUNT:0987654321\nACCOUNT_NAME:DRINKFLOW TWO");
    }

    public function test_admin_cannot_delete_payment_account_used_by_live_campaign(): void
    {
        $admin = $this->admin('payments-live-campaign@example.test');
        $room = $this->roomFor($admin, 'payments-live-campaign-room');
        $account = PaymentAccount::create([
            'room_id' => $room->id,
            'bank_code' => 'VCB',
            'bank_name' => 'Vietcombank',
            'account_number' => '0123456789',
            'account_name' => 'DRINKFLOW',
            'status' => 'active',
        ]);
        Campaign::create([
            'room_id' => $room->id,
            'name' => 'Live campaign',
            'restaurant' => 'DrinkFlow',
            'payment_account_id' => $account->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin, 'admin')->deleteJson("/admin/{$room->slug}/payment-accounts/{$account->id}")
            ->assertUnprocessable()
            ->assertJsonPath('message', __('admin.payment_account_live_campaign_blocked'));

        $this->assertDatabaseHas('payment_accounts', ['id' => $account->id, 'status' => 'active']);
    }

    public function test_admin_permanently_deletes_payment_account(): void
    {
        $admin = $this->admin('payments-delete@example.test');
        $room = $this->roomFor($admin, 'payments-delete-room');
        $account = PaymentAccount::create([
            'room_id' => $room->id,
            'bank_code' => 'VCB',
            'bank_name' => 'Vietcombank',
            'account_number' => '0123456789',
            'account_name' => 'DRINKFLOW',
            'status' => 'active',
        ]);

        $this->actingAs($admin, 'admin')->deleteJson("/admin/{$room->slug}/payment-accounts/{$account->id}")
            ->assertOk()
            ->assertJsonPath('data.deleted', true);

        $this->assertDatabaseMissing('payment_accounts', ['id' => $account->id]);
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

    /**
     * Ensure the dashboard renders expired campaign timing and the chart legend layout.
     *
     * @return void
     */
    public function test_admin_dashboard_shows_expired_time_and_bottom_chart_legend(): void
    {
        $admin = $this->admin('dashboard-expired@example.test');
        $room = $this->roomFor($admin, 'dashboard-expired-room');
        Campaign::create([
            'room_id' => $room->id,
            'name' => 'Expired Live Campaign',
            'restaurant' => 'Cafe',
            'status' => CampaignStatus::Active,
            'started_at' => now()->subHours(2),
            'deadline' => now()->subMinute(),
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard.page', $room))
            ->assertOk()
            ->assertSee('data-time-expired-text="'.__('admin.time_expired').'"', false)
            ->assertSee(__('admin.time_expired'))
            ->assertSee('data-adjust-campaign-link', false)
            ->assertSee(__('admin.adjust_campaign'))
            ->assertSee(route('admin.campaigns.show', [$room, $room->campaigns()->first()]))
            ->assertDontSee('id="chart-date-range"', false)
            ->assertSeeInOrder([
                'id="chart-day-labels"',
                'id="chart-legend"',
                __('admin.campaign_count_bar'),
                __('admin.spending_vnd_line'),
            ], false);
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
        \Illuminate\Support\Facades\Mail::fake();
        $admin = $this->admin('recovery@example.test');

        // Step 1: Request OTP
        $this->post('/admin/forgot-password', [
            'email' => 'recovery@example.test',
        ])->assertRedirect(route('admin.verify-otp.page'));

        $this->assertTrue(session()->has('admin_reset_otp'));
        $otp = session('admin_reset_otp');

        // Assert OTP email was dispatched
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\AdminResetPasswordOtpMail::class, function ($mail) use ($otp) {
            return $mail->hasTo('recovery@example.test') && $mail->otp === $otp;
        });

        // Step 2: Verify Invalid OTP fails
        $this->post('/admin/verify-otp', [
            'otp' => '000000',
        ])->assertSessionHasErrors('otp');

        // Step 3: Verify Valid OTP succeeds and redirects with temporary signed URL
        $response = $this->post('/admin/verify-otp', [
            'otp' => $otp,
        ]);

        $this->assertTrue(session('admin_reset_verified'));
        $targetUrl = $response->headers->get('Location');
        $this->assertNotNull($targetUrl);
        $this->assertStringContainsString('signature=', $targetUrl);

        // Step 4a: Access reset password page without signature fails
        $this->get('/admin/reset-password')->assertRedirect(route('admin.forgot-password.page'));

        // Step 4b: Access reset password page with tampered signature fails
        $this->get('/admin/reset-password?signature=invalid_tampered_sig')->assertRedirect(route('admin.forgot-password.page'));

        // Step 4c: Access reset password page with valid signed URL succeeds
        $this->get($targetUrl)->assertOk();

        // Step 4d: Reset Password with valid signed URL query
        $parsed = parse_url($targetUrl);
        $queryStr = $parsed['query'] ?? '';
        $this->post('/admin/reset-password?' . $queryStr, [
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
