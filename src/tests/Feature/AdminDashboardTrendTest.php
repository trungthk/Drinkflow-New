<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Models\Admin;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDashboardTrendTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The weekly trend reports per-day spending and order counts (cancelled orders excluded) and flags the peak day.
     */
    public function test_weekly_trend_reports_spending_orders_and_peak_day(): void
    {
        $admin = Admin::create([
            'name' => 'Room Admin',
            'email' => 'trend-admin@example.test',
            'password' => Hash::make('secret'),
            'status' => 'active',
        ]);
        $room = Room::create(['name' => 'Trend Room', 'slug' => 'trend-room', 'status' => 'active']);
        $admin->rooms()->attach($room);
        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Trà chiều',
            'restaurant' => 'Cafe',
            'status' => CampaignStatus::Active,
        ]);
        $globalUser = GlobalUser::create(['name' => 'Member', 'email' => 'member-trend@example.test']);
        $roomUser = RoomUser::create([
            'room_id' => $room->id,
            'global_user_id' => $globalUser->id,
            'display_name' => 'Member',
            'status' => 'active',
        ]);

        foreach ([[OrderStatus::Submitted, 30000], [OrderStatus::Completed, 20000], [OrderStatus::Cancelled, 90000]] as [$status, $amount]) {
            Order::create([
                'room_id' => $room->id,
                'campaign_id' => $campaign->id,
                'room_user_id' => $roomUser->id,
                'subtotal' => $amount,
                'final_amount' => $amount,
                'status' => $status,
            ]);
        }

        $response = $this->actingAs($admin, 'admin')
            ->getJson("/admin/{$room->id}/dashboard/data")
            ->assertOk()
            ->assertJsonPath('data.weekly_total_spending', 50000);

        $trend = $response->json('data.weekly_trend');
        $today = $trend[6];
        $this->assertSame(50000, $today['spending_amount']);
        $this->assertSame(2, $today['orders_count']);
        $this->assertSame(1, $today['campaigns_count']);
        $this->assertTrue($today['is_peak']);
        $this->assertSame(0, $trend[0]['spending_amount']);
        $this->assertFalse($trend[0]['is_peak']);
    }

    /**
     * Spending is the campaigns' gross total (order subtotals + delivery fee - discount, before sponsorship),
     * counted on the day the campaign was created; cancelled campaigns and campaigns without orders spend nothing.
     */
    public function test_weekly_trend_spending_uses_campaign_gross_total(): void
    {
        $admin = Admin::create([
            'name' => 'Room Admin',
            'email' => 'gross-admin@example.test',
            'password' => Hash::make('secret'),
            'status' => 'active',
        ]);
        $room = Room::create(['name' => 'Gross Room', 'slug' => 'gross-room', 'status' => 'active']);
        $admin->rooms()->attach($room);
        $globalUser = GlobalUser::create(['name' => 'Member', 'email' => 'gross-member@example.test']);
        $roomUser = RoomUser::create(['room_id' => $room->id, 'global_user_id' => $globalUser->id, 'display_name' => 'Member', 'status' => 'active']);

        $campaign = Campaign::create([
            'room_id' => $room->id, 'name' => 'Trà chiều', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Closed,
            'delivery_fee' => 15000, 'discount' => 5000,
        ]);
        foreach ([[OrderStatus::Completed, 40000], [OrderStatus::Cancelled, 90000]] as [$status, $subtotal]) {
            Order::create([
                'room_id' => $room->id, 'campaign_id' => $campaign->id, 'room_user_id' => $roomUser->id,
                'subtotal' => $subtotal, 'sponsor_amount' => 30000, 'final_amount' => $subtotal - 30000, 'status' => $status,
            ]);
        }
        // Delivery fee alone never counts as spending.
        Campaign::create(['room_id' => $room->id, 'name' => 'Không ai đặt', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Closed, 'delivery_fee' => 20000]);
        $cancelled = Campaign::create(['room_id' => $room->id, 'name' => 'Đã hủy', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Cancelled, 'delivery_fee' => 20000]);
        Order::create([
            'room_id' => $room->id, 'campaign_id' => $cancelled->id, 'room_user_id' => $roomUser->id,
            'subtotal' => 70000, 'final_amount' => 70000, 'status' => OrderStatus::Completed,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->getJson("/admin/{$room->id}/dashboard/data")
            ->assertOk()
            // 40,000 subtotal + 15,000 delivery - 5,000 discount; the sponsored 30,000 is still spending.
            ->assertJsonPath('data.weekly_total_spending', 50000);

        $this->assertSame(50000, $response->json('data.weekly_trend.6.spending_amount'));
        $this->assertSame(3, $response->json('data.weekly_trend.6.campaigns_count'));
    }

    /**
     * The "running" badge of the open-campaigns card and the "needs settlement" badge of the unpaid-debt card are gone.
     */
    public function test_dashboard_metric_cards_have_no_status_badges(): void
    {
        $admin = Admin::create([
            'name' => 'Room Admin',
            'email' => 'badge-admin@example.test',
            'password' => Hash::make('secret'),
            'status' => 'active',
        ]);
        $room = Room::create(['name' => 'Badge Room', 'slug' => 'badge-room', 'status' => 'active']);
        $admin->rooms()->attach($room);

        $html = $this->actingAs($admin, 'admin')
            ->get("/admin/{$room->slug}/dashboard")
            ->assertOk()
            ->getContent();

        $grid = substr($html, (int) strpos($html, 'id="metrics-grid"'), 12000);
        $grid = substr($grid, 0, (int) strpos($grid, '</section>'));
        $this->assertStringNotContainsString(__('admin.needs_settlement'), $grid);
        $this->assertStringNotContainsString('Đang chạy', $grid);
    }

    /**
     * The dashboard page hands the chart its localized labels through data attributes.
     */
    public function test_dashboard_exposes_localized_chart_labels(): void
    {
        $admin = Admin::create([
            'name' => 'Room Admin',
            'email' => 'trend-admin2@example.test',
            'password' => Hash::make('secret'),
            'status' => 'active',
        ]);
        $room = Room::create(['name' => 'Trend Room 2', 'slug' => 'trend-room-2', 'status' => 'active']);
        $admin->rooms()->attach($room);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard.page', $room->slug))
            ->assertOk()
            ->assertSee('data-chart-label-spending="'.__('admin.chart_tooltip_spending').'"', false)
            ->assertSee('data-chart-label-campaigns="'.__('admin.chart_tooltip_campaigns').'"', false)
            ->assertSee('data-chart-label-orders="'.__('admin.chart_tooltip_orders').'"', false)
            ->assertSee('data-chart-peak-label="'.__('admin.chart_peak_label').'"', false);
    }

    /**
     * The dashboard exposes the number of active room members (blocked members are not counted), both in the API and the page.
     */
    public function test_dashboard_reports_active_room_members_count(): void
    {
        $admin = Admin::create([
            'name' => 'Room Admin',
            'email' => 'members-admin@example.test',
            'password' => Hash::make('secret'),
            'status' => 'active',
        ]);
        $room = Room::create(['name' => 'Members Room', 'slug' => 'members-room', 'status' => 'active']);
        $admin->rooms()->attach($room);

        foreach (['active', 'active', 'blocked'] as $index => $status) {
            $globalUser = GlobalUser::create(['name' => "Member {$index}", 'email' => "member-count-{$index}@example.test"]);
            RoomUser::create([
                'room_id' => $room->id,
                'global_user_id' => $globalUser->id,
                'display_name' => "Member {$index}",
                'status' => $status,
            ]);
        }

        $this->actingAs($admin, 'admin')
            ->getJson("/admin/{$room->id}/dashboard/data")
            ->assertOk()
            ->assertJsonPath('data.active_room_users', 2);

        $this->actingAs($admin, 'admin')
            ->get("/admin/{$room->slug}/dashboard")
            ->assertOk()
            ->assertSee(__('admin.across_members', ['count' => 2]));
    }

    /**
     * The dashboard no longer shows the "recently received orders" table, even when orders exist.
     */
    public function test_dashboard_has_no_recent_orders_table(): void
    {
        $admin = Admin::create([
            'name' => 'Room Admin',
            'email' => 'recent-orders-admin@example.test',
            'password' => Hash::make('secret'),
            'status' => 'active',
        ]);
        $room = Room::create(['name' => 'Recent Room', 'slug' => 'recent-room', 'status' => 'active']);
        $admin->rooms()->attach($room);
        $campaign = Campaign::create(['room_id' => $room->id, 'name' => 'Trà chiều', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Active]);
        $globalUser = GlobalUser::create(['name' => 'Member', 'email' => 'recent-member@example.test']);
        $roomUser = RoomUser::create(['room_id' => $room->id, 'global_user_id' => $globalUser->id, 'display_name' => 'Member', 'status' => 'active']);
        $order = Order::create([
            'room_id' => $room->id,
            'campaign_id' => $campaign->id,
            'room_user_id' => $roomUser->id,
            'subtotal' => 30000,
            'final_amount' => 30000,
            'status' => OrderStatus::Submitted,
        ]);

        $this->actingAs($admin, 'admin')
            ->get("/admin/{$room->slug}/dashboard")
            ->assertOk()
            ->assertDontSee('recent-orders-section', false)
            ->assertDontSee('#' . $order->code);
    }

    public function test_campaign_order_and_debt_metrics_link_to_their_pages(): void
    {
        $admin = Admin::create([
            'name' => 'Room Admin',
            'email' => 'metric-links-admin@example.test',
            'password' => Hash::make('secret'),
            'status' => 'active',
        ]);
        $room = Room::create(['name' => 'Metric Links Room', 'slug' => 'metric-links-room', 'status' => 'active']);
        $admin->rooms()->attach($room);

        $html = $this->actingAs($admin, 'admin')
            ->get("/admin/{$room->slug}/dashboard")
            ->assertOk()
            ->getContent();

        $links = [
            route('admin.campaigns.page', [$room, 'status' => CampaignStatus::Active->value]) => 'metric-live-campaigns',
            route('admin.orders.page', $room) => 'metric-orders-today',
            route('admin.debts.page', $room) => 'metric-unpaid-debt',
        ];
        foreach ($links as $url => $metricId) {
            // The metric value sits inside the card link pointing to its management page.
            $pattern = '/<a href="' . preg_quote(e($url), '/') . '" data-dashboard-metric-link(?:(?!<\/a>).)*id="' . $metricId . '"/s';
            $this->assertMatchesRegularExpression($pattern, $html);
        }
        $this->assertSame(3, substr_count($html, 'data-dashboard-metric-link'));
    }
}
