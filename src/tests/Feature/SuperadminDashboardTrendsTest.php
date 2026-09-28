<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Models\AdminAccount;
use App\Models\Campaign;
use App\Models\ContactInquiry;
use App\Models\Feedback;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\Room;
use App\Models\RoomUser;
use App\Models\SystemMetricSnapshot;
use App\Services\Dashboard\SuperadminTrendsService;
use App\Services\System\SystemHealthService;
use App\Services\System\SystemMetricsRecorder;
use App\Services\System\SystemSettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use Tests\TestCase;

class SuperadminDashboardTrendsTest extends TestCase
{
    use RefreshDatabase;

    private AdminAccount $root;
    private Room $room;
    private Campaign $campaign;
    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        // Maintenance state is memoized per process; a room admin would get 503 if an earlier test left it on.
        SystemSettingsService::clearCache();
        Carbon::setTestNow('2026-09-28 12:00:00');

        $this->root = AdminAccount::create([
            'name' => 'Root', 'email' => 'root-trends@drinkflow.test',
            'password' => 'password123', 'role' => AdminRole::SuperAdmin, 'status' => 'active',
        ]);
        $this->room = Room::create(['name' => 'Trend Room', 'slug' => 'trend-room', 'status' => 'active']);
        $this->campaign = Campaign::create(['room_id' => $this->room->id, 'status' => CampaignStatus::Closed, 'name' => 'C', 'restaurant' => 'Shop']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function member(string $createdAt): RoomUser
    {
        $n = ++$this->sequence;
        $user = GlobalUser::create(['name' => "Member {$n}", 'email' => "member-trends-{$n}@drinkflow.test"]);
        $user->forceFill(['created_at' => $createdAt])->save();

        return RoomUser::create(['room_id' => $this->room->id, 'global_user_id' => $user->id, 'display_name' => "Member {$n}", 'status' => 'active']);
    }

    private function order(RoomUser $member, string $createdAt, OrderStatus $status = OrderStatus::Completed): void
    {
        $order = Order::create([
            'room_id' => $this->room->id, 'campaign_id' => $this->campaign->id, 'room_user_id' => $member->id,
            'code' => 'ORD-TREND-' . (++$this->sequence), 'subtotal' => 1000, 'final_amount' => 1000, 'status' => $status,
        ]);
        $order->forceFill(['created_at' => $createdAt])->save();
    }

    public function test_trends_endpoint_is_superadmin_only(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Room admin', 'email' => 'room-admin-trends@drinkflow.test',
            'password' => 'password123', 'role' => AdminRole::Admin, 'status' => 'active',
        ]);

        $this->actingAs($admin, 'admin')->getJson(route('superadmin.dashboard.trends'))->assertForbidden();
    }

    public function test_trends_endpoint_returns_payload(): void
    {
        $this->actingAs($this->root, 'admin')->getJson(route('superadmin.dashboard.trends'))
            ->assertOk()
            ->assertJsonStructure(['data' => ['cohorts', 'feedback' => ['monthly', 'distribution'], 'contact_topics', 'system_history' => ['points'], 'generated_at']]);
    }

    public function test_cohorts_measure_ordering_retention_by_months_since_signup(): void
    {
        // July cohort: two members; one orders in July and again in September, one never orders.
        $loyal = $this->member('2026-07-05 09:00:00');
        $this->member('2026-07-20 09:00:00');
        $this->order($loyal, '2026-07-06 10:00:00');
        $this->order($loyal, '2026-09-10 10:00:00');
        $this->order($loyal, '2026-09-11 10:00:00', OrderStatus::Cancelled);
        // September cohort: one member who orders the same month.
        $this->order($this->member('2026-09-01 09:00:00'), '2026-09-02 10:00:00');
        // Signed up before the window: ignored.
        $this->order($this->member('2026-01-01 09:00:00'), '2026-09-02 11:00:00');

        $cohorts = collect(app(SuperadminTrendsService::class)->cohorts(CarbonImmutable::now()))->keyBy('month');

        $this->assertCount(SuperadminTrendsService::COHORT_MONTHS, $cohorts);
        $this->assertSame('2026-04', $cohorts->keys()->first());
        $this->assertSame(2, $cohorts['2026-07']['size']);
        $this->assertSame([50.0, 0.0, 50.0], array_column($cohorts['2026-07']['retention'], 'rate'));
        $this->assertSame([['offset' => 0, 'active' => 1, 'rate' => 100.0]], $cohorts['2026-09']['retention']);
        $this->assertSame(0, $cohorts['2026-04']['size']);
    }

    public function test_feedback_reports_monthly_average_and_distribution(): void
    {
        foreach ([['2026-09-03', 5], ['2026-09-04', 3], ['2026-08-10', 4], ['2025-12-01', 1]] as [$date, $rating]) {
            $feedback = Feedback::create(['rating' => $rating, 'content' => 'Nice', 'status' => 'active']);
            $feedback->forceFill(['created_at' => "{$date} 10:00:00"])->save();
        }

        $feedback = app(SuperadminTrendsService::class)->feedback(CarbonImmutable::now());
        $monthly = collect($feedback['monthly'])->keyBy('month');

        $this->assertSame(['month' => '2026-09', 'count' => 2, 'average' => 4.0], $monthly['2026-09']);
        $this->assertNull($monthly['2026-07']['average']);
        $this->assertSame([1 => 0, 2 => 0, 3 => 1, 4 => 1, 5 => 1], $feedback['distribution']);
        $this->assertSame(3, $feedback['count']);
        $this->assertSame(4.0, $feedback['average']);
    }

    public function test_contact_topics_list_every_topic_most_frequent_first(): void
    {
        foreach (['deploy', 'deploy', 'vietqr'] as $i => $topic) {
            ContactInquiry::create([
                'ticket_code' => "T-{$i}", 'full_name' => 'A', 'work_email' => 'a@example.com', 'phone' => '0900000000',
                'company' => 'Co', 'topic' => $topic, 'message' => 'Hi',
            ]);
        }

        $topics = app(SuperadminTrendsService::class)->contactTopics(CarbonImmutable::now());

        $this->assertSame(['topic' => 'deploy', 'count' => 2], $topics[0]);
        $this->assertSame(['topic' => 'vietqr', 'count' => 1], $topics[1]);
        $this->assertCount(count(\App\Enums\ContactTopic::cases()), $topics);
    }

    public function test_system_history_reports_uptime_and_storage_forecast(): void
    {
        // Storage grows 10 bytes/day with 100 bytes free at the latest reading → 10 days left.
        foreach ([[3, 870, true, true], [2, 880, true, false], [1, 890, false, true], [0, 900, true, null]] as [$daysAgo, $used, $dbOk, $socketOk]) {
            SystemMetricSnapshot::create([
                'captured_at' => now()->subDays($daysAgo), 'database_ok' => $dbOk, 'pending_jobs' => 0, 'failed_jobs' => 1,
                'storage_used_bytes' => $used, 'storage_total_bytes' => 1000, 'socket_ok' => $socketOk, 'socket_connections' => 3,
            ]);
        }
        SystemMetricSnapshot::create(['captured_at' => now()->subDays(20), 'failed_jobs' => 0]);

        $history = app(SuperadminTrendsService::class)->systemHistory(CarbonImmutable::now());

        $this->assertCount(4, $history['points']);
        $this->assertSame(75.0, $history['database_uptime']);
        $this->assertSame(66.7, $history['socket_uptime']);
        $this->assertSame(10, $history['storage_days_left']);
    }

    public function test_capture_command_stores_snapshot_and_prunes_old_ones(): void
    {
        SystemMetricSnapshot::create(['captured_at' => now()->subDays(40), 'failed_jobs' => 0]);
        $this->mock(SystemHealthService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('queue')->andReturn(['connection' => 'database', 'failed_jobs' => 2]);
            $mock->shouldReceive('database')->andReturn('ok');
            $mock->shouldReceive('socket')->andReturn(['status' => 'healthy', 'connected_users' => 4, 'connected_admins' => 1, 'connected_superadmins' => null]);
        });

        $this->artisan('drinkflow:capture-system-metrics')->assertSuccessful();

        $snapshot = SystemMetricSnapshot::query()->sole();
        $this->assertSame(2, $snapshot->failed_jobs);
        $this->assertTrue($snapshot->database_ok);
        $this->assertTrue($snapshot->socket_ok);
        $this->assertSame(5, $snapshot->socket_connections);
        $this->assertTrue($snapshot->captured_at->isToday());
    }

    public function test_recorder_leaves_socket_unknown_when_gateway_is_not_configured(): void
    {
        $this->mock(SystemHealthService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('queue')->andReturn(['connection' => 'sync', 'failed_jobs' => 0]);
            $mock->shouldReceive('database')->andReturn('ok');
            $mock->shouldReceive('socket')->andReturn(['status' => 'unknown', 'connected_users' => null]);
        });

        $snapshot = app(SystemMetricsRecorder::class)->capture();

        $this->assertNull($snapshot->socket_ok);
        $this->assertNull($snapshot->socket_connections);
    }
}
