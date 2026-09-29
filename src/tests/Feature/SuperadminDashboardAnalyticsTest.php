<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Enums\DebtStatus;
use App\Enums\OrderStatus;
use App\Models\Admin;
use App\Models\Superadmin;
use App\Models\Campaign;
use App\Models\Debt;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\Room;
use App\Models\RoomUser;
use App\Models\SecurityEvent;
use App\Services\Dashboard\SuperadminDashboardService;
use App\Services\System\SystemSettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SuperadminDashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private Superadmin $root;
    private Room $room;
    private RoomUser $member;
    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        // Maintenance state is memoized per process; a room admin would get 503 if an earlier test left it on.
        SystemSettingsService::clearCache();
        Carbon::setTestNow('2026-09-28 12:00:00');

        $this->root = $this->createSuperadmin([
            'name' => 'Root', 'email' => 'root-analytics@drinkflow.test',
            'password' => 'password123', 'status' => 'active',
        ]);
        $this->room = Room::create(['name' => 'Busy Room', 'slug' => 'busy-room', 'status' => 'active']);
        $user = GlobalUser::create(['name' => 'Member', 'email' => 'member-analytics@drinkflow.test']);
        $this->member = RoomUser::create([
            'room_id' => $this->room->id, 'global_user_id' => $user->id, 'display_name' => 'Member', 'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function campaign(Room $room, CampaignStatus $status, string $createdAt, ?string $deadline = null): Campaign
    {
        $campaign = Campaign::create([
            'room_id' => $room->id, 'status' => $status, 'name' => 'Campaign', 'restaurant' => 'Shop', 'deadline' => $deadline,
        ]);
        $campaign->forceFill(['created_at' => $createdAt])->save();

        return $campaign;
    }

    private function order(Campaign $campaign, int $amount, OrderStatus $status, string $createdAt): Order
    {
        $order = Order::create([
            'room_id' => $campaign->room_id, 'campaign_id' => $campaign->id, 'room_user_id' => $this->member->id,
            'code' => 'ORD-TEST-' . (++$this->sequence), 'subtotal' => $amount, 'final_amount' => $amount, 'status' => $status,
        ]);
        $order->forceFill(['created_at' => $createdAt])->save();

        return $order;
    }

    private function debt(Campaign $campaign, int $remaining, int $paid, DebtStatus $status, string $createdAt): Debt
    {
        $debt = Debt::create([
            'room_id' => $campaign->room_id, 'campaign_id' => $campaign->id, 'room_user_id' => $this->member->id,
            'code' => 'DEBT-TEST-' . (++$this->sequence), 'original_amount' => $remaining + $paid, 'paid_amount' => $paid, 'remaining_amount' => $remaining, 'status' => $status,
        ]);
        $debt->forceFill(['created_at' => $createdAt])->save();

        return $debt;
    }

    public function test_analytics_endpoint_returns_payload_for_superadmin(): void
    {
        $this->actingAs($this->root, 'superadmin')->getJson(route('superadmin.dashboard.analytics'))
            ->assertOk()
            ->assertJsonStructure(['data' => ['period_days', 'kpis', 'daily', 'debt_aging', 'rooms', 'stuck_campaigns', 'generated_at']]);
    }

    public function test_analytics_endpoint_rejects_guests_and_room_admins(): void
    {
        $admin = Admin::create([
            'name' => 'Room admin', 'email' => 'room-admin-analytics@drinkflow.test',
            'password' => 'password123', 'status' => 'active',
        ]);

        $this->getJson(route('superadmin.dashboard.analytics'))->assertUnauthorized();
        $this->actingAs($admin, 'admin')->getJson(route('superadmin.dashboard.analytics'))->assertUnauthorized();
    }

    public function test_kpis_compare_current_and_previous_period_and_ignore_cancelled_orders(): void
    {
        $current = $this->campaign($this->room, CampaignStatus::Closed, '2026-09-20 10:00:00');
        $previous = $this->campaign($this->room, CampaignStatus::Closed, '2026-08-10 10:00:00');
        $this->order($current, 30000, OrderStatus::Completed, '2026-09-20 10:30:00');
        $this->order($current, 99000, OrderStatus::Cancelled, '2026-09-20 10:40:00');
        $this->order($previous, 20000, OrderStatus::Completed, '2026-08-10 10:30:00');
        $this->debt($current, 10000, 30000, DebtStatus::Partial, '2026-09-20 12:00:00');
        SecurityEvent::create(['type' => 'failed_login', 'severity' => 'high', 'created_at' => '2026-09-28 08:00:00']);
        SecurityEvent::create(['type' => 'failed_login', 'severity' => 'medium', 'created_at' => '2026-09-28 08:00:00']);

        $kpis = app(SuperadminDashboardService::class)->kpis(CarbonImmutable::now());

        $this->assertSame(['value' => 1, 'previous' => 1], $kpis['active_rooms']);
        $this->assertSame(['value' => 1, 'previous' => 1], $kpis['active_users']);
        $this->assertSame(['value' => 30000, 'previous' => 20000], $kpis['gmv']);
        $this->assertSame(75.0, $kpis['collection_rate']['value']);
        $this->assertNull($kpis['collection_rate']['previous']);
        $this->assertSame(10000, $kpis['outstanding_debt']['value']);
        $this->assertSame(1, $kpis['outstanding_debt']['count']);
        $this->assertSame(['value' => 1, 'previous' => 0], $kpis['high_security_events']);
    }

    public function test_daily_series_is_zero_filled_for_the_whole_period(): void
    {
        $campaign = $this->campaign($this->room, CampaignStatus::Closed, '2026-09-27 09:00:00');
        $this->order($campaign, 25000, OrderStatus::Completed, '2026-09-27 09:30:00');
        $this->order($campaign, 15000, OrderStatus::Submitted, '2026-09-27 11:00:00');

        $daily = app(SuperadminDashboardService::class)->dailySeries(CarbonImmutable::now());

        $this->assertCount(SuperadminDashboardService::PERIOD_DAYS, $daily);
        $this->assertSame('2026-08-30', $daily[0]['date']);
        $this->assertSame(['date' => '2026-09-27', 'orders' => 2, 'gmv' => 40000], $daily[28]);
        $this->assertSame(['date' => '2026-09-28', 'orders' => 0, 'gmv' => 0], $daily[29]);
    }

    public function test_debt_aging_groups_outstanding_debt_by_age(): void
    {
        $campaign = $this->campaign($this->room, CampaignStatus::Closed, '2026-06-01 09:00:00');
        $this->debt($campaign, 1000, 0, DebtStatus::Unpaid, '2026-09-28 09:00:00');
        $second = $this->campaign($this->room, CampaignStatus::Closed, '2026-06-01 09:00:00');
        $this->debt($second, 2000, 0, DebtStatus::Pending, '2026-09-21 09:00:00');
        $third = $this->campaign($this->room, CampaignStatus::Closed, '2026-06-01 09:00:00');
        $this->debt($third, 4000, 0, DebtStatus::Partial, '2026-09-20 09:00:00');
        $fourth = $this->campaign($this->room, CampaignStatus::Closed, '2026-06-01 09:00:00');
        $this->debt($fourth, 8000, 0, DebtStatus::Unpaid, '2026-07-01 09:00:00');
        $paid = $this->campaign($this->room, CampaignStatus::Closed, '2026-06-01 09:00:00');
        $this->debt($paid, 0, 5000, DebtStatus::Paid, '2026-07-01 09:00:00');

        $aging = collect(app(SuperadminDashboardService::class)->debtAging(CarbonImmutable::now()))->keyBy('bucket');

        $this->assertSame(['0_7', '8_30', '31_60', '60_plus'], $aging->keys()->all());
        $this->assertSame(3000, $aging['0_7']['amount']);
        $this->assertSame(2, $aging['0_7']['count']);
        $this->assertSame(4000, $aging['8_30']['amount']);
        $this->assertSame(0, $aging['31_60']['amount']);
        $this->assertSame(8000, $aging['60_plus']['amount']);
    }

    public function test_room_health_flags_dormant_bad_debt_and_missing_admin(): void
    {
        $quiet = Room::create(['name' => 'Quiet Room', 'slug' => 'quiet-room', 'status' => 'active']);
        $quiet->forceFill(['created_at' => '2026-05-01 00:00:00'])->save();
        Room::create(['name' => 'Archived Room', 'slug' => 'archived-room', 'status' => 'archived']);
        $admin = Admin::create([
            'name' => 'Room admin', 'email' => 'busy-admin@drinkflow.test',
            'password' => 'password123', 'status' => 'active',
        ]);
        $admin->rooms()->attach($this->room->id);

        $recent = $this->campaign($this->room, CampaignStatus::Closed, '2026-09-25 09:00:00');
        $this->order($recent, 30000, OrderStatus::Completed, '2026-09-25 09:30:00');
        $this->debt($recent, 1000, 0, DebtStatus::Unpaid, '2026-09-25 10:00:00');
        $old = $this->campaign($quiet, CampaignStatus::Closed, '2026-06-01 09:00:00');
        $this->debt($old, 9000, 0, DebtStatus::Unpaid, '2026-06-01 10:00:00');

        $rooms = collect(app(SuperadminDashboardService::class)->roomHealth(CarbonImmutable::now()))->keyBy('slug');

        $this->assertFalse($rooms->has('archived-room'));
        $this->assertSame([], $rooms['busy-room']['flags']);
        $this->assertSame(1, $rooms['busy-room']['orders']);
        $this->assertSame(30000, $rooms['busy-room']['gmv']);
        $this->assertSame(1, $rooms['busy-room']['active_admins']);
        $this->assertSame(1, $rooms['busy-room']['members_active']);
        $this->assertSame(
            [SuperadminDashboardService::FLAG_DORMANT, SuperadminDashboardService::FLAG_BAD_DEBT, SuperadminDashboardService::FLAG_NO_ADMIN],
            $rooms['quiet-room']['flags'],
        );
        $this->assertSame(100.0, $rooms['quiet-room']['debt_overdue_ratio']);
        // Rooms with more warnings come first.
        $this->assertSame('quiet-room', $rooms->keys()->first());
    }

    public function test_room_health_flags_high_cancel_ratio(): void
    {
        $this->campaign($this->room, CampaignStatus::Cancelled, '2026-09-20 09:00:00');
        $this->campaign($this->room, CampaignStatus::Closed, '2026-09-21 09:00:00');
        $this->campaign($this->room, CampaignStatus::Closed, '2026-09-22 09:00:00');

        $room = app(SuperadminDashboardService::class)->roomHealth(CarbonImmutable::now())[0];

        $this->assertContains(SuperadminDashboardService::FLAG_HIGH_CANCEL, $room['flags']);
        $this->assertSame(1, $room['campaigns_cancelled']);
    }

    public function test_stuck_campaigns_lists_open_campaigns_past_deadline_grace(): void
    {
        $stuck = $this->campaign($this->room, CampaignStatus::Active, '2026-09-27 08:00:00', '2026-09-28 06:00:00');
        $this->order($stuck, 30000, OrderStatus::Submitted, '2026-09-28 05:00:00');
        $this->campaign($this->room, CampaignStatus::Active, '2026-09-28 08:00:00', '2026-09-28 11:00:00');
        $this->campaign($this->room, CampaignStatus::Closed, '2026-09-20 08:00:00', '2026-09-20 09:00:00');

        $campaigns = app(SuperadminDashboardService::class)->stuckCampaigns(CarbonImmutable::now());

        $this->assertCount(1, $campaigns);
        $this->assertSame($stuck->id, $campaigns[0]['id']);
        $this->assertSame(6, $campaigns[0]['overdue_hours']);
        $this->assertSame(1, $campaigns[0]['orders']);
        $this->assertSame('Busy Room', $campaigns[0]['room']);
    }
}
