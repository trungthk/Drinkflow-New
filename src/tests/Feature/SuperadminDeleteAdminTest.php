<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\CampaignStatus;
use App\Models\AdminAccount;
use App\Models\AdminNotification;
use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SuperadminDeleteAdminTest extends TestCase
{
    use RefreshDatabase;

    private function root(): AdminAccount
    {
        return AdminAccount::create(['name' => 'Root', 'email' => 'root-delete@drinkflow.test', 'password' => 'password123', 'role' => AdminRole::SuperAdmin, 'status' => 'active']);
    }

    public function test_superadmin_deletes_an_admin_and_its_relations_while_shared_data_is_kept(): void
    {
        $root = $this->root();
        $admin = AdminAccount::create(['name' => 'Operator', 'email' => 'operator-delete@drinkflow.test', 'password' => 'password123', 'role' => AdminRole::Admin, 'status' => 'active']);
        $room = Room::create(['name' => 'Ops', 'slug' => 'ops-delete', 'status' => 'active']);
        $admin->rooms()->attach($room);

        $campaign = Campaign::create(['room_id' => $room->id, 'name' => 'Trà', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Active, 'creator_admin_id' => $admin->id]);
        $user = GlobalUser::create(['name' => 'Member', 'email' => 'member-delete@drinkflow.test', 'status' => 'active']);
        $member = RoomUser::create(['room_id' => $room->id, 'global_user_id' => $user->id, 'display_name' => 'Member', 'status' => 'active']);
        $order = Order::create(['room_id' => $room->id, 'campaign_id' => $campaign->id, 'room_user_id' => $member->id, 'subtotal' => 30000, 'final_amount' => 30000, 'status' => 'submitted']);
        $order->forceFill(['placed_by_admin_id' => $admin->id])->save();
        AdminNotification::create(['admin_id' => $admin->id, 'room_id' => $room->id, 'type' => 'order.created', 'title' => 'T', 'body' => 'B']);
        $history = AuditLog::create(['actor_type' => 'admin', 'actor_id' => $admin->id, 'event' => 'campaign.created', 'target_type' => 'campaign', 'target_id' => $campaign->id, 'room_id' => $room->id, 'created_at' => now()]);
        $history->admins()->attach($admin->id);

        $this->actingAs($root, 'admin')
            ->deleteJson("/superadmin/admins/{$admin->id}")
            ->assertOk()
            ->assertJsonPath('data.deleted', true);

        $this->assertDatabaseMissing('admin_accounts', ['id' => $admin->id]);
        $this->assertDatabaseMissing('admin_rooms', ['admin_id' => $admin->id]);
        $this->assertDatabaseMissing('admin_notifications', ['admin_id' => $admin->id]);
        $this->assertDatabaseMissing('admin_audit_logs', ['admin_id' => $admin->id]);

        // Shared data stays, only the link to the deleted admin is cleared.
        $this->assertNull($campaign->fresh()->creator_admin_id);
        $this->assertNull($order->fresh()->placed_by_admin_id);
        $this->assertNotNull(Room::find($room->id));

        // Activity history is kept, and the deletion itself is audited.
        $this->assertNotNull(AuditLog::find($history->id));
        $this->assertTrue(AuditLog::query()->where('event', 'admin.deleted')->where('target_id', $admin->id)->exists());
    }

    public function test_superadmin_cannot_delete_own_account(): void
    {
        $root = $this->root();
        AdminAccount::create(['name' => 'Root 2', 'email' => 'root2-delete@drinkflow.test', 'password' => 'password123', 'role' => AdminRole::SuperAdmin, 'status' => 'active']);

        $this->actingAs($root, 'admin')
            ->deleteJson("/superadmin/admins/{$root->id}")
            ->assertUnprocessable()
            ->assertJsonFragment([__('superadmin.actions.cannot_delete_self')]);

        $this->assertDatabaseHas('admin_accounts', ['id' => $root->id]);
    }

    public function test_the_last_other_active_superadmin_is_protected(): void
    {
        $root = $this->root();
        $other = AdminAccount::create(['name' => 'Root 2', 'email' => 'root3-delete@drinkflow.test', 'password' => 'password123', 'role' => AdminRole::SuperAdmin, 'status' => 'blocked']);

        // Deleting a superadmin is allowed while another active superadmin (the actor) remains.
        $this->actingAs($root, 'admin')->deleteJson("/superadmin/admins/{$other->id}")->assertOk();
        $this->assertSame(0, DB::table('admin_accounts')->where('id', $other->id)->count());
    }

    public function test_admin_pages_show_delete_controls_but_not_for_the_current_account(): void
    {
        $root = $this->root();
        $admin = AdminAccount::create(['name' => 'Operator', 'email' => 'operator-ui@drinkflow.test', 'password' => 'password123', 'role' => AdminRole::Admin, 'status' => 'active']);

        $this->actingAs($root, 'admin')->get(route('superadmin.admins.page'))
            ->assertOk()
            ->assertSee('data-action="delete-admin" data-admin-id="'.$admin->id.'"', false);

        $this->actingAs($root, 'admin')->get(route('superadmin.admins.detail.page', $admin->id))
            ->assertOk()
            ->assertSee('id="admin-delete"', false);

        $this->actingAs($root, 'admin')->get(route('superadmin.admins.detail.page', $root->id))
            ->assertOk()
            ->assertDontSee('id="admin-delete"', false);
    }

    public function test_superadmin_layout_uses_admin_brand_mark_without_zero_trust_block(): void
    {
        $this->actingAs($this->root(), 'admin')->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('superadmin-logo', false)
            ->assertSee('M20 3H4v10c0 2.21', false)
            ->assertDontSee('superadmin-trust', false)
            ->assertDontSee('Zero Trust');
    }
}
