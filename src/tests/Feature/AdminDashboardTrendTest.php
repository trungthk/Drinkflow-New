<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Models\AdminAccount;
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
        $admin = AdminAccount::create([
            'name' => 'Room Admin',
            'email' => 'trend-admin@example.test',
            'password' => Hash::make('secret'),
            'role' => AdminRole::Admin,
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
     * The dashboard page hands the chart its localized labels through data attributes.
     */
    public function test_dashboard_exposes_localized_chart_labels(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Room Admin',
            'email' => 'trend-admin2@example.test',
            'password' => Hash::make('secret'),
            'role' => AdminRole::Admin,
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
        $admin = AdminAccount::create([
            'name' => 'Room Admin',
            'email' => 'members-admin@example.test',
            'password' => Hash::make('secret'),
            'role' => AdminRole::Admin,
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
}
