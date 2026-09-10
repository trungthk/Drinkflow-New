<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Models\AdminAccount;
use App\Models\Campaign;
use App\Models\CampaignItem;
use App\Models\PaymentAccount;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $email = 'admin@example.test'): AdminAccount
    {
        return AdminAccount::create(['name' => 'Room Admin', 'email' => $email, 'password' => 'secret', 'role' => AdminRole::Admin, 'status' => 'active']);
    }

    private function roomFor(AdminAccount $admin, string $slug = 'admin-room'): Room
    {
        $room = Room::create(['name' => 'Admin Room', 'slug' => $slug, 'status' => 'active']);
        $admin->rooms()->attach($room);
        return $room;
    }

    public function test_admin_dashboard_is_limited_to_assigned_room(): void
    {
        $admin = $this->admin();
        $room = $this->roomFor($admin);
        $otherRoom = Room::create(['name' => 'Other', 'slug' => 'other-room', 'status' => 'active']);

        $this->actingAs($admin, 'admin')->getJson("/admin/{$room->id}/dashboard/data")->assertOk()->assertJsonPath('data.active_campaigns', 0);
        $this->actingAs($admin, 'admin')->getJson("/admin/{$otherRoom->id}/dashboard/data")->assertForbidden();
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
}
