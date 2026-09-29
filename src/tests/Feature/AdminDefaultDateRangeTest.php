<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\Room;
use App\Models\RoomUser;
use App\Support\Helpers\DateRangeHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDefaultDateRangeTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name' => 'Room Admin',
            'email' => 'date-range-admin@example.test',
            'password' => Hash::make('secret'),
            'status' => 'active',
        ]);
        $this->room = Room::create(['name' => 'Range Room', 'slug' => 'range-room', 'status' => 'active']);
        $this->admin->rooms()->attach($this->room);
    }

    public function test_last_days_helper_matches_the_seven_day_preset(): void
    {
        [$from, $to] = DateRangeHelper::lastDays();

        $this->assertSame(now()->toDateString(), $to);
        $this->assertSame(now()->subDays(6)->toDateString(), $from);
    }

    public function test_audit_page_defaults_to_last_seven_days(): void
    {
        $recent = AuditLog::create(['actor_type' => 'admin', 'actor_id' => 1, 'event' => 'recent.event', 'target_type' => 'campaign', 'target_id' => 1, 'room_id' => $this->room->id, 'created_at' => now()->subDays(2)]);
        $old = AuditLog::create(['actor_type' => 'admin', 'actor_id' => 1, 'event' => 'old.event', 'target_type' => 'campaign', 'target_id' => 2, 'room_id' => $this->room->id, 'created_at' => now()->subDays(10)]);
        [$from, $to] = DateRangeHelper::lastDays();

        $this->actingAs($this->admin, 'admin')->get(route('admin.audit.page', $this->room))
            ->assertOk()
            ->assertSee('name="date_from" value="'.$from.'"', false)
            ->assertSee('name="date_to" value="'.$to.'"', false)
            ->assertSee(__('admin.preset_last_7_days'))
            ->assertViewHas('logs', fn ($logs): bool => $logs->pluck('id')->all() === [$recent->id]);

        // Choosing "Tất cả" submits an empty range, which shows the whole history.
        $this->actingAs($this->admin, 'admin')->get(route('admin.audit.page', ['room' => $this->room, 'date_from' => '', 'date_to' => '']))
            ->assertOk()
            ->assertViewHas('logs', fn ($logs): bool => $logs->pluck('id')->sort()->values()->all() === [$recent->id, $old->id]);
    }

    public function test_reports_default_to_last_seven_days_and_period_all_covers_history(): void
    {
        $campaign = Campaign::create(['room_id' => $this->room->id, 'name' => 'Range', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Closed]);
        // Create every order first (codes are numbered per day), then move them back in time.
        $orders = [];
        foreach ([2, 10] as $index => $daysAgo) {
            $user = GlobalUser::create(['name' => "M{$index}", 'email' => "range-{$index}@example.test", 'status' => 'active']);
            $member = RoomUser::create(['room_id' => $this->room->id, 'global_user_id' => $user->id, 'display_name' => "M{$index}", 'status' => 'active']);
            $order = Order::create([
                'room_id' => $this->room->id,
                'campaign_id' => $campaign->id,
                'room_user_id' => $member->id,
                'subtotal' => 30000,
                'final_amount' => 30000,
                'status' => OrderStatus::Completed,
            ]);
            $orders[$daysAgo] = $order;
        }
        foreach ($orders as $daysAgo => $order) {
            $order->forceFill(['created_at' => now()->subDays($daysAgo)])->save();
        }
        [$from, $to] = DateRangeHelper::lastDays();

        $this->actingAs($this->admin, 'admin')->get(route('admin.reports.page', $this->room))
            ->assertOk()
            ->assertSee('name="date_from" value="'.$from.'"', false)
            ->assertSee('name="date_to" value="'.$to.'"', false)
            ->assertViewHas('stats', fn (array $stats): bool => $stats['order_count'] === 1);

        $this->actingAs($this->admin, 'admin')->get(route('admin.reports.page', ['room' => $this->room, 'period' => 'all']))
            ->assertOk()
            ->assertViewHas('stats', fn (array $stats): bool => $stats['order_count'] === 2);
    }
}
