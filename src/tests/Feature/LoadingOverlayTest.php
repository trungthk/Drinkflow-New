<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Models\Admin;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoadingOverlayTest extends TestCase
{
    use RefreshDatabase;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->room = Room::create(['name' => 'Loading Room', 'slug' => 'loading-room', 'status' => 'active']);
        Campaign::create([
            'room_id' => $this->room->id,
            'name' => 'Trà chiều',
            'restaurant' => 'Cafe',
            'status' => CampaignStatus::Active,
            'deadline' => now()->addHour(),
        ]);
    }

    public function test_admin_dashboard_uses_loading_overlay_for_trend_chart_and_close_summary(): void
    {
        $admin = Admin::create([
            'name' => 'Room Admin',
            'email' => 'loading-admin@example.test',
            'password' => Hash::make('secret'),
            'status' => 'active',
        ]);
        $admin->rooms()->attach($this->room);

        $html = $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard.page', $this->room))
            ->assertOk()
            ->assertSee(__('admin.loading_data'))
            ->assertSee(__('admin.close_summary_loading'))
            ->getContent();

        $this->assertMatchesRegularExpression('/<div[^>]*data-trend-loading[^>]*data-loading-overlay/s', $html);
        $this->assertMatchesRegularExpression('/<div[^>]*data-close-summary-loading[^>]*data-loading-overlay/s', $html);
    }

    public function test_room_dashboard_charts_use_loading_overlay(): void
    {
        $user = GlobalUser::create(['name' => 'Member', 'email' => 'loading-member@example.test', 'status' => 'active']);
        RoomUser::create(['room_id' => $this->room->id, 'global_user_id' => $user->id, 'display_name' => 'Member', 'status' => 'active']);

        $html = $this->actingAs($user, 'web')
            ->get(route('user.dashboard', $this->room->slug))
            ->assertOk()
            ->assertSee(__('global.common.loading'))
            ->getContent();

        // One overlay for "Top nhà tài trợ" and one for the 7-day items & value chart.
        $this->assertSame(2, preg_match_all('/<div[^>]*data-chart-loading[^>]*data-loading-overlay/s', $html));
    }
}
