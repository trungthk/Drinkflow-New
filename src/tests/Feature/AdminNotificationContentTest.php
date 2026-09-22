<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\CampaignStatus;
use App\Models\AdminAccount;
use App\Models\AdminNotification;
use App\Models\Campaign;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminNotificationContentTest extends TestCase
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

    /**
     * Test a general campaign info edit produces a specific, non-generic admin notification.
     */
    public function test_campaign_info_update_generates_specific_admin_notification(): void
    {
        $admin = $this->admin('info-update@example.test');
        $room = $this->roomFor($admin, 'info-update-room');
        $campaign = Campaign::create(['room_id' => $room->id, 'name' => 'Trà chiều', 'restaurant' => 'Gong Cha', 'status' => CampaignStatus::Active]);

        $this->actingAs($admin, 'admin')
            ->patchJson("/admin/{$room->slug}/campaigns/{$campaign->id}", ['restaurant' => 'Highlands'])
            ->assertOk();

        $notification = AdminNotification::where('room_id', $room->id)->where('type', 'campaign.updated')->first();
        $this->assertNotNull($notification);
        $this->assertSame('Chiến dịch vừa mới cập nhật thông tin.', $notification->body);
    }

    /**
     * Test adjusting only delivery fee/discount produces a distinct notification type and body,
     * separate from a general info update.
     */
    public function test_campaign_fee_adjustment_generates_distinct_admin_notification(): void
    {
        $admin = $this->admin('fee-adjust@example.test');
        $room = $this->roomFor($admin, 'fee-adjust-room');
        $campaign = Campaign::create(['room_id' => $room->id, 'name' => 'Trà chiều', 'restaurant' => 'Gong Cha', 'status' => CampaignStatus::Active]);

        $this->actingAs($admin, 'admin')
            ->patchJson("/admin/{$room->slug}/campaigns/{$campaign->id}", ['delivery_fee' => 15000, 'discount' => 5000])
            ->assertOk();

        $this->assertDatabaseMissing('admin_notifications', ['room_id' => $room->id, 'type' => 'campaign.updated']);
        $notification = AdminNotification::where('room_id', $room->id)->where('type', 'campaign.fee_adjusted')->first();
        $this->assertNotNull($notification);
        $this->assertSame('Chiến dịch vừa mới cập nhật giảm giá & chi phí giao hàng.', $notification->body);
    }

    /**
     * Test toggling a menu item's status produces a notification naming the specific item and action.
     */
    public function test_campaign_item_status_update_generates_notification_with_item_name(): void
    {
        $admin = $this->admin('item-status@example.test');
        $room = $this->roomFor($admin, 'item-status-room');
        $campaign = Campaign::create(['room_id' => $room->id, 'name' => 'Trà chiều', 'restaurant' => 'Gong Cha', 'status' => CampaignStatus::Active]);
        $item = $campaign->items()->create(['name' => 'Trà đào cam sả', 'normalized_name' => 'tra dao cam sa', 'base_price' => 45000, 'status' => 'active']);

        $this->actingAs($admin, 'admin')
            ->patchJson("/admin/{$room->slug}/campaigns/{$campaign->id}/items/{$item->id}/status", ['status' => 'inactive'])
            ->assertOk();

        $notification = AdminNotification::where('room_id', $room->id)->where('type', 'campaign_item.status_updated')->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Trà đào cam sả', $notification->body);
        $this->assertStringContainsString('ẩn khỏi', $notification->body);
    }

    /**
     * Test the admin notification dropdown renders a type-specific icon instead of a hardcoded one.
     */
    public function test_admin_notification_dropdown_renders_type_specific_icon(): void
    {
        $admin = $this->admin('icon-check@example.test');
        $room = $this->roomFor($admin, 'icon-check-room');

        AdminNotification::create([
            'admin_id' => $admin->id,
            'room_id' => $room->id,
            'type' => 'campaign.updated',
            'title' => 'campaign.updated',
            'data' => ['after' => ['name' => 'Trà chiều']],
        ]);

        $this->actingAs($admin, 'admin')
            ->get("/admin/{$room->slug}/dashboard")
            ->assertOk()
            ->assertSee('local_fire_department', false);
    }
}
