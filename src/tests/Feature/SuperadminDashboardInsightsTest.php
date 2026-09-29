<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Models\Admin;
use App\Models\Superadmin;
use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\Room;
use App\Models\RoomUser;
use App\Models\SecurityEvent;
use App\Services\Dashboard\SuperadminInsightsService;
use App\Services\System\SystemSettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SuperadminDashboardInsightsTest extends TestCase
{
    use RefreshDatabase;

    private Superadmin $root;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        // Maintenance state is memoized per process; a room admin would get 503 if an earlier test left it on.
        SystemSettingsService::clearCache();
        Carbon::setTestNow('2026-09-28 12:00:00');
        config(['app.display_timezone' => 'Asia/Ho_Chi_Minh']);

        $this->root = $this->createSuperadmin([
            'name' => 'Root', 'email' => 'root-insights@drinkflow.test', 'password' => 'password123',
            'status' => 'active', 'last_login_at' => '2026-09-28 08:00:00',
        ]);
        $this->room = Room::create(['name' => 'Insight Room', 'slug' => 'insight-room', 'status' => 'active']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function securityEvent(string $type, string $severity, ?string $ip, string $createdAt): void
    {
        SecurityEvent::create(['type' => $type, 'severity' => $severity, 'ip_address' => $ip, 'created_at' => $createdAt]);
    }

    public function test_insights_endpoint_is_superadmin_only(): void
    {
        $admin = Admin::create([
            'name' => 'Room admin', 'email' => 'room-admin-insights@drinkflow.test',
            'password' => 'password123', 'status' => 'active',
        ]);

        $this->actingAs($admin, 'admin')->getJson(route('superadmin.dashboard.insights'))->assertUnauthorized();
    }

    public function test_insights_endpoint_returns_payload(): void
    {
        $this->actingAs($this->root, 'superadmin')->getJson(route('superadmin.dashboard.insights'))
            ->assertOk()
            ->assertJsonStructure(['data' => ['security' => ['daily', 'types', 'top_ips'], 'admins', 'heatmap' => ['timezone', 'cells', 'max', 'total'], 'generated_at']]);
    }

    public function test_security_daily_splits_by_severity_and_types_are_ranked(): void
    {
        $this->securityEvent('failed_login', 'medium', '10.0.0.1', '2026-09-28 09:00:00');
        $this->securityEvent('failed_login', 'medium', '10.0.0.1', '2026-09-28 10:00:00');
        $this->securityEvent('invalid_company_domain', 'high', null, '2026-09-27 10:00:00');
        $this->securityEvent('failed_login', 'low', null, '2026-08-01 10:00:00');

        $service = app(SuperadminInsightsService::class);
        $daily = $service->securityDaily(CarbonImmutable::now());

        $this->assertCount(SuperadminInsightsService::SECURITY_DAYS, $daily);
        $this->assertSame(['date' => '2026-09-28', 'low' => 0, 'medium' => 2, 'high' => 0], $daily[13]);
        $this->assertSame(['date' => '2026-09-27', 'low' => 0, 'medium' => 0, 'high' => 1], $daily[12]);
        $this->assertSame(
            [['type' => 'failed_login', 'count' => 2], ['type' => 'invalid_company_domain', 'count' => 1]],
            $service->securityTypes(CarbonImmutable::now()),
        );
    }

    public function test_top_ips_flag_repeated_failed_logins_in_the_window(): void
    {
        for ($i = 0; $i < SuperadminInsightsService::SUSPICIOUS_FAILED_LOGINS; $i++) {
            $this->securityEvent('failed_login', 'medium', '203.0.113.9', '2026-09-28 11:0' . ($i % 10) . ':00');
        }
        $this->securityEvent('google_oauth_failure', 'medium', '198.51.100.4', '2026-09-28 11:00:00');
        $this->securityEvent('failed_login', 'medium', '192.0.2.1', '2026-09-26 11:00:00');

        $ips = app(SuperadminInsightsService::class)->topIps(CarbonImmutable::now());

        $this->assertCount(2, $ips);
        $this->assertSame('203.0.113.9', $ips[0]['ip_address']);
        $this->assertSame(SuperadminInsightsService::SUSPICIOUS_FAILED_LOGINS, $ips[0]['failed_logins']);
        $this->assertTrue($ips[0]['suspicious']);
        $this->assertFalse($ips[1]['suspicious']);
    }

    public function test_admin_activity_counts_work_and_flags_stale_accounts(): void
    {
        $busy = Admin::create([
            'name' => 'Busy', 'email' => 'busy-insights@drinkflow.test', 'password' => 'password123',
            'status' => 'active', 'last_login_at' => '2026-09-27 08:00:00',
        ]);
        $busy->rooms()->attach($this->room->id);
        $stale = Admin::create([
            'name' => 'Stale', 'email' => 'stale-insights@drinkflow.test', 'password' => 'password123',
            'status' => 'active', 'last_login_at' => '2026-06-01 08:00:00',
        ]);
        Campaign::create(['room_id' => $this->room->id, 'status' => CampaignStatus::Closed, 'name' => 'C', 'restaurant' => 'Shop', 'creator_admin_id' => $busy->id]);
        AuditLog::create(['actor_type' => AuditLog::ACTOR_ADMIN, 'actor_id' => $busy->id, 'event' => 'campaign.created', 'target_type' => 'campaign', 'target_id' => 1, 'created_at' => now()]);

        $admins = collect(app(SuperadminInsightsService::class)->adminActivity(CarbonImmutable::now()))->keyBy('email');

        $this->assertSame([], $admins['busy-insights@drinkflow.test']['flags']);
        $this->assertSame(1, $admins['busy-insights@drinkflow.test']['campaigns_created']);
        $this->assertSame(1, $admins['busy-insights@drinkflow.test']['audit_actions']);
        $this->assertSame(1, $admins['busy-insights@drinkflow.test']['rooms']);
        $this->assertSame(
            [SuperadminInsightsService::FLAG_STALE, SuperadminInsightsService::FLAG_NO_ROOMS],
            $admins['stale-insights@drinkflow.test']['flags'],
        );
        // Superadmins are separate accounts and never appear in the Admin activity list.
        $this->assertFalse($admins->has('root-insights@drinkflow.test'));
        $this->assertSame('stale-insights@drinkflow.test', $admins->keys()->first());
    }

    public function test_order_heatmap_buckets_by_local_weekday_and_hour(): void
    {
        $user = GlobalUser::create(['name' => 'Member', 'email' => 'member-insights@drinkflow.test']);
        $member = RoomUser::create(['room_id' => $this->room->id, 'global_user_id' => $user->id, 'display_name' => 'Member', 'status' => 'active']);
        $campaign = Campaign::create(['room_id' => $this->room->id, 'status' => CampaignStatus::Closed, 'name' => 'C', 'restaurant' => 'Shop']);
        // 2026-09-28 is a Monday; 03:30 UTC is 10:30 in Asia/Ho_Chi_Minh.
        foreach ([['2026-09-28 03:30:00', OrderStatus::Completed], ['2026-09-28 03:45:00', OrderStatus::Submitted], ['2026-09-28 03:50:00', OrderStatus::Cancelled]] as $i => [$createdAt, $status]) {
            $order = Order::create([
                'room_id' => $this->room->id, 'campaign_id' => $campaign->id, 'room_user_id' => $member->id,
                'code' => 'ORD-HEAT-' . $i, 'subtotal' => 1000, 'final_amount' => 1000, 'status' => $status,
            ]);
            $order->forceFill(['created_at' => $createdAt])->save();
        }

        $heatmap = app(SuperadminInsightsService::class)->orderHeatmap(CarbonImmutable::now());

        $this->assertSame('Asia/Ho_Chi_Minh', $heatmap['timezone']);
        $this->assertSame(2, $heatmap['cells'][0][10]);
        $this->assertSame(2, $heatmap['total']);
        $this->assertSame(2, $heatmap['max']);
    }
}
