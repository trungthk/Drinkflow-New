<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\CampaignStatus;
use App\Models\AdminAccount;
use App\Models\Campaign;
use App\Models\CampaignItem;
use App\Models\PaymentAccount;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google.allowed_domains' => []]);
    }

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

    public function test_admin_can_upload_and_display_avatar_without_public_storage_link(): void
    {
        Storage::fake('public');
        $admin = $this->admin('avatar-admin@example.test');

        $response = $this->actingAs($admin, 'admin')
            ->from('/admin/profile')
            ->post('/admin/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('avatar.png', 800, 600),
            ]);

        $response->assertRedirect('/admin/profile')->assertSessionHasNoErrors();
        $storedPath = (string) $admin->fresh()->avatar_url;
        $this->assertStringStartsWith('uploads/admin-avatars/', $storedPath);
        Storage::disk('public')->assertExists($storedPath);

        $this->actingAs($admin->fresh(), 'admin')
            ->get(route('admin.profile.avatar.show'))
            ->assertOk()
            ->assertHeader('content-type', 'image/webp');

        $this->actingAs($admin->fresh(), 'admin')
            ->get('/admin/profile')
            ->assertOk()
            ->assertSee('data-admin-avatar="sidebar"', false)
            ->assertSee('admin-profile-chip', false)
            ->assertDontSee('data-admin-avatar="header"', false)
            ->assertSee(route('admin.profile.avatar.show'), false);
    }

    public function test_order_management_translates_campaign_sponsor_type(): void
    {
        $admin = $this->admin('sponsor-translation@example.test');
        $room = $this->roomFor($admin, 'sponsor-translation-room');
        Campaign::create([
            'room_id' => $room->id,
            'name' => 'Sponsored Lunch',
            'restaurant' => 'Cafe',
            'status' => CampaignStatus::Active,
            'sponsor_type' => 'full',
            'sponsor_description' => 'Company benefit',
        ]);

        $this->actingAs($admin, 'admin')
            ->withSession(['locale' => 'en'])
            ->get(route('admin.orders.page', $room->slug))
            ->assertOk()
            ->assertSee(__('admin.sponsor_type_full'));
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

    public function test_admin_can_create_campaign_with_complete_menu_structure(): void
    {
        $admin = $this->admin('nested-menu@example.test');
        $room = $this->roomFor($admin, 'nested-menu-room');

        $response = $this->actingAs($admin, 'admin')->postJson("/admin/{$room->id}/campaigns", [
            'name' => 'Menu đầy đủ',
            'restaurant' => 'DrinkFlow Cafe',
            'sponsor_type' => 'none',
            'max_budget' => 500000,
            'status' => 'draft',
            'items' => [[
                'category' => 'Trà sữa',
                'name' => 'Trà sữa trân châu',
                'price' => 45000,
                'description' => 'Ít ngọt',
                'image_url' => 'https://example.com/images/milk-tea.jpg',
                'toppings' => [
                    ['name' => 'Pudding', 'price' => 10000],
                ],
                'options' => [
                    ['name' => 'Size L', 'price_delta' => 12000],
                ],
            ]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.items.0.name', 'Trà sữa trân châu')
            ->assertJsonPath('data.items.0.toppings.0.name', 'Pudding')
            ->assertJsonPath('data.items.0.sizes.0.name', 'Size L');

        $campaignId = $response->json('data.id');
        $this->assertDatabaseHas('campaign_items', [
            'campaign_id' => $campaignId,
            'name' => 'Trà sữa trân châu',
            'base_price' => 45000,
            'image_url' => 'https://example.com/images/milk-tea.jpg',
        ]);
        $this->assertDatabaseHas('campaign_item_toppings', ['name' => 'Pudding', 'price' => 10000]);
        $this->assertDatabaseHas('campaign_item_sizes', ['name' => 'Size L', 'price_delta' => 12000]);
    }

    public function test_campaign_creator_uses_full_width_menu_workflow(): void
    {
        $admin = $this->admin('campaign-create-ui@example.test');
        $room = $this->roomFor($admin, 'campaign-create-ui-room');
        $this->app->setLocale('vi');

        $response = $this->actingAs($admin, 'admin')->get("/admin/{$room->id}/campaigns/create");

        $response->assertOk()
            ->assertSee('class="w-full space-y-6"', false)
            ->assertSeeInOrder([__('admin.source_previous'), __('admin.source_data_gateway'), __('admin.source_crawler'), __('admin.selected_menu_preview')])
            ->assertSee(__('admin.add_manual_item_button'))
            ->assertSee('data-deadline-payment-grid', false)
            ->assertSee('data-search-debounce="300"', false)
            ->assertSee(__('admin.search_sponsor_placeholder'))
            ->assertSeeInOrder([__('admin.menu_view_all'), __('admin.menu_view_category')])
            ->assertSee('openEditItemModal(entry.index)', false)
            ->assertSeeInOrder([__('admin.item_tab_basic'), __('admin.item_tab_additional')])
            ->assertSee('clampSponsorPercentage(sponsor)', false)
            ->assertSee('itemSubmitting', false)
            ->assertSee('no_food', false)
            ->assertDontSee('+30m')
            ->assertDontSee('+60m');
    }

    public function test_admin_can_upload_campaign_menu_image(): void
    {
        Storage::fake('public');
        $admin = $this->admin('menu-image@example.test');
        $room = $this->roomFor($admin, 'menu-image-room');

        $response = $this->actingAs($admin, 'admin')->post("/admin/{$room->id}/campaigns/menu-images", [
            'image' => UploadedFile::fake()->image('drink.png', 600, 400),
        ], ['Accept' => 'application/json']);

        $response->assertCreated();
        $imageUrl = (string) $response->json('data.url');
        $this->assertStringStartsWith('/storage/uploads/campaigns/', $imageUrl);
        $this->assertStringNotContainsString('://', $imageUrl);
        $this->assertCount(1, Storage::disk('public')->allFiles('uploads/campaigns'));
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

    public function test_admin_campaigns_list_shows_expired_badge_for_active_campaign_with_past_deadline(): void
    {
        $admin = $this->admin('expiredcamp@example.test');
        $room = $this->roomFor($admin, 'expiredcamp-room');

        $expiredCampaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Expired Active Campaign',
            'restaurant' => 'Phúc Long',
            'status' => CampaignStatus::Active,
            'deadline' => now()->subHour(),
        ]);

        $activeFutureCampaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Active Future Campaign',
            'restaurant' => 'Highlands',
            'status' => CampaignStatus::Active,
            'deadline' => now()->addHour(),
        ]);

        $closedCampaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Closed Campaign',
            'restaurant' => 'Gong Cha',
            'status' => CampaignStatus::Closed,
            'deadline' => now()->subHours(2),
        ]);

        $response = $this->actingAs($admin, 'admin')->get("/admin/{$room->slug}/campaigns/list");
        $response->assertOk()
            ->assertSee('Expired Active Campaign')
            ->assertSee('Active Future Campaign')
            ->assertSee(__('admin.status_expired'))
            ->assertSee(__('admin.filter_active'))
            ->assertSee(__('admin.filter_closed'))
            ->assertSee("/admin/{$room->slug}/campaigns/{$expiredCampaign->id}/edit")
            ->assertDontSee('onclick="deleteCampaign(');
    }

    public function test_admin_can_view_edit_campaign_page(): void
    {
        $admin = $this->admin('editcamp@example.test');
        $room = $this->roomFor($admin, 'editcamp-room');

        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Coffee To Edit',
            'restaurant' => 'Highlands',
            'status' => CampaignStatus::Draft,
            'max_budget' => 1500000,
        ]);

        $item = $campaign->items()->create([
            'name' => 'Trà Đào',
            'normalized_name' => 'TRA DAO',
            'category' => 'Trà',
            'base_price' => 45000,
            'status' => 'active',
        ]);
        $item->toppings()->create(['name' => 'Thạch Đào', 'price' => 10000]);
        $item->sizes()->create(['name' => 'Size L', 'price_delta' => 8000]);

        $response = $this->actingAs($admin, 'admin')->get("/admin/{$room->slug}/campaigns/{$campaign->id}/edit");
        $response->assertOk()
            ->assertViewIs('admin.campaign-edit')
            ->assertSee('Coffee To Edit')
            ->assertSee('Highlands')
            ->assertSee('Trà Đào');
    }

    public function test_admin_can_update_campaign_metadata_and_sync_items(): void
    {
        $admin = $this->admin('updatecamp@example.test');
        $room = $this->roomFor($admin, 'updatecamp-room');

        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Original Campaign Name',
            'restaurant' => 'Original Restaurant',
            'status' => CampaignStatus::Draft,
            'max_budget' => 1000000,
        ]);

        $existingItem = $campaign->items()->create([
            'name' => 'Món Cũ',
            'normalized_name' => 'MON CU',
            'category' => 'Cà phê',
            'base_price' => 30000,
            'status' => 'active',
        ]);

        $payload = [
            'name' => 'Updated Campaign Name',
            'restaurant' => 'Updated Restaurant',
            'description' => 'Updated Description',
            'max_budget' => 1200000,
            'sponsor_type' => 'none',
            'items' => [
                [
                    'id' => $existingItem->id,
                    'name' => 'Món Cũ Đã Sửa',
                    'category' => 'Cà phê',
                    'price' => 35000,
                    'toppings' => [
                        ['name' => 'Sữa đặc', 'price' => 5000],
                    ],
                    'options' => [
                        ['name' => 'Size L', 'price_delta' => 7000],
                    ],
                ],
                [
                    'name' => 'Món Mới Thêm',
                    'category' => 'Trà',
                    'price' => 50000,
                    'toppings' => [],
                    'options' => [],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'admin')->patchJson("/admin/{$room->id}/campaigns/{$campaign->id}", $payload);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Campaign Name')
            ->assertJsonPath('data.restaurant', 'Updated Restaurant');

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'name' => 'Updated Campaign Name',
            'restaurant' => 'Updated Restaurant',
            'description' => 'Updated Description',
            'max_budget' => 1200000,
        ]);

        $this->assertDatabaseHas('campaign_items', [
            'id' => $existingItem->id,
            'campaign_id' => $campaign->id,
            'name' => 'Món Cũ Đã Sửa',
            'base_price' => 35000,
        ]);

        $this->assertDatabaseHas('campaign_items', [
            'campaign_id' => $campaign->id,
            'name' => 'Món Mới Thêm',
            'base_price' => 50000,
        ]);

        $this->assertDatabaseHas('campaign_item_toppings', [
            'campaign_item_id' => $existingItem->id,
            'name' => 'Sữa đặc',
            'price' => 5000,
        ]);

        $this->assertDatabaseHas('campaign_item_sizes', [
            'campaign_item_id' => $existingItem->id,
            'name' => 'Size L',
            'price_delta' => 7000,
        ]);
    }

    public function test_admin_can_add_brand_new_user_to_room(): void
    {
        $admin = $this->admin('adminaddnew@example.test');
        $room = $this->roomFor($admin, 'room-add-new-user');

        $response = $this->actingAs($admin, 'admin')->postJson("/admin/{$room->slug}/room-users", [
            'email' => 'brandnewmember@example.test',
            'name' => 'New Guy',
            'phone' => '0987654321',
            'desk_location' => 'Floor 3',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.display_name', 'New Guy');

        $this->assertDatabaseHas('global_users', [
            'email' => 'brandnewmember@example.test',
            'name' => 'New Guy',
            'phone' => '0987654321',
            'desk_location' => 'Floor 3',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('room_users', [
            'room_id' => $room->id,
            'display_name' => 'New Guy',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_add_existing_global_user_to_room(): void
    {
        $admin = $this->admin('adminaddexist@example.test');
        $room = $this->roomFor($admin, 'room-add-exist-user');

        $existingGlobalUser = \App\Models\GlobalUser::create([
            'email' => 'existingglobal@example.test',
            'name' => 'Existing Global Person',
            'normalized_name' => 'EXISTING GLOBAL PERSON',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'admin')->postJson("/admin/{$room->slug}/room-users", [
            'email' => 'existingglobal@example.test',
            'name' => 'Existing Global Person',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('room_users', [
            'room_id' => $room->id,
            'global_user_id' => $existingGlobalUser->id,
            'status' => 'active',
        ]);
    }

    public function test_admin_cannot_add_duplicate_active_user_to_room(): void
    {
        $admin = $this->admin('adminadddup@example.test');
        $room = $this->roomFor($admin, 'room-add-dup-user');

        $globalUser = \App\Models\GlobalUser::create([
            'email' => 'alreadyinroom@example.test',
            'name' => 'Already In Room',
            'normalized_name' => 'ALREADY IN ROOM',
            'status' => 'active',
        ]);

        \App\Models\RoomUser::create([
            'room_id' => $room->id,
            'global_user_id' => $globalUser->id,
            'user_code' => 'ALREADY',
            'display_name' => 'Already In Room',
            'normalized_name' => 'ALREADY IN ROOM',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'admin')->postJson("/admin/{$room->slug}/room-users", [
            'email' => 'alreadyinroom@example.test',
            'name' => 'Already In Room',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_admin_reactivates_removed_user_when_adding_again(): void
    {
        $admin = $this->admin('adminreactivate@example.test');
        $room = $this->roomFor($admin, 'room-reactivate-user');

        $globalUser = \App\Models\GlobalUser::create([
            'email' => 'removeduser@example.test',
            'name' => 'Removed Person',
            'normalized_name' => 'REMOVED PERSON',
            'status' => 'active',
        ]);

        $roomUser = \App\Models\RoomUser::create([
            'room_id' => $room->id,
            'global_user_id' => $globalUser->id,
            'user_code' => 'REMOVED',
            'display_name' => 'Removed Person',
            'normalized_name' => 'REMOVED PERSON',
            'status' => 'removed',
        ]);

        $response = $this->actingAs($admin, 'admin')->postJson("/admin/{$room->slug}/room-users", [
            'email' => 'removeduser@example.test',
            'name' => 'Removed Person (Rejoined)',
        ]);

        $response->assertCreated();
        $this->assertEquals('active', $roomUser->fresh()->status->value);
        $this->assertEquals('Removed Person (Rejoined)', $roomUser->fresh()->display_name);
    }

    public function test_unauthorized_admin_cannot_add_room_user(): void
    {
        $admin1 = $this->admin('otheradmin@example.test');
        $admin2 = $this->admin('roomadmin@example.test');
        $room = $this->roomFor($admin2, 'room-restricted');

        $response = $this->actingAs($admin1, 'admin')->postJson("/admin/{$room->slug}/room-users", [
            'email' => 'unauthuser@example.test',
            'name' => 'Unauth User',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_cannot_add_user_with_unauthorized_email_domain(): void
    {
        config(['services.google.allowed_domains' => ['company.com']]);

        $admin = $this->admin('admin@company.com');
        $room = $this->roomFor($admin, 'room-domain-check');

        $response = $this->actingAs($admin, 'admin')->postJson("/admin/{$room->slug}/room-users", [
            'email' => 'outsider@gmail.com',
            'name' => 'Outsider User',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_admin_can_add_user_with_authorized_email_domain(): void
    {
        config(['services.google.allowed_domains' => ['company.com']]);

        $admin = $this->admin('admin@company.com');
        $room = $this->roomFor($admin, 'room-domain-ok');

        $response = $this->actingAs($admin, 'admin')->postJson("/admin/{$room->slug}/room-users", [
            'email' => 'insider@company.com',
            'name' => 'Insider User',
        ]);

        $response->assertCreated();
    }
}
