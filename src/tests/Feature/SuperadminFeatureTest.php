<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\CampaignStatus;
use App\Models\AdminAccount;
use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\OAuthIdentity;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperadminFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): AdminAccount
    {
        return AdminAccount::create(['name' => 'Root', 'email' => 'root@drinkflow.test', 'password' => 'password123', 'role' => AdminRole::SuperAdmin, 'status' => 'active']);
    }

    public function test_superadmin_can_create_room_and_assign_admin(): void
    {
        $root = $this->superadmin();
        $room = Room::create(['name' => 'IT', 'slug' => 'it']);
        $response = $this->actingAs($root, 'admin')->postJson('/superadmin/admins', ['name' => 'Operator', 'email' => 'operator@drinkflow.test', 'password' => 'password123', 'room_ids' => [$room->id]]);

        $response->assertCreated()->assertJsonPath('data.rooms.0.id', $room->id);
        $this->assertDatabaseHas('admin_rooms', ['admin_id' => $response->json('data.id'), 'room_id' => $room->id]);
        $this->actingAs($root, 'admin')->postJson('/superadmin/rooms', ['name' => 'Marketing', 'slug' => 'marketing'])->assertCreated();
    }

    public function test_superadmin_can_sync_admin_rooms_and_archive_room(): void
    {
        $root = $this->superadmin();
        $admin = AdminAccount::create(['name' => 'Operator', 'email' => 'operator-rooms@drinkflow.test', 'password' => 'password123', 'role' => AdminRole::Admin, 'status' => 'active']);
        $room = Room::create(['name' => 'Operations', 'slug' => 'operations', 'status' => 'active']);

        $this->actingAs($root, 'admin')->putJson("/superadmin/admins/{$admin->id}/rooms", ['room_ids' => [$room->id]])
            ->assertOk()
            ->assertJsonPath('data.id', $admin->id)
            ->assertJsonPath('data.rooms.0.id', $room->id);
        $this->assertDatabaseHas('admin_rooms', ['admin_id' => $admin->id, 'room_id' => $room->id]);

        $this->actingAs($root, 'admin')->patchJson("/superadmin/rooms/{$room->id}", ['name' => 'Operations HQ', 'slug' => 'operations-hq'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Operations HQ');
        $this->actingAs($root, 'admin')->patchJson("/superadmin/rooms/{$room->id}/status", ['status' => 'archived'])
            ->assertOk()
            ->assertJsonPath('data.status', 'archived');
    }

    /**
     * The room create modal must be able to assign responsible admins and an initial status
     * in the same request that creates the room.
     *
     * @return void
     */
    public function test_superadmin_can_create_room_with_status_and_admins(): void
    {
        $root = $this->superadmin();
        $admin = AdminAccount::create(['name' => 'Room Lead', 'email' => 'room-lead@drinkflow.test', 'password' => 'password123', 'role' => AdminRole::Admin, 'status' => 'active']);

        $response = $this->actingAs($root, 'admin')->postJson('/superadmin/rooms', [
            'name' => 'Sales', 'slug' => 'sales', 'status' => 'inactive', 'admin_ids' => [$admin->id],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'inactive')
            ->assertJsonPath('data.admins.0.id', $admin->id);
        $this->assertDatabaseHas('admin_rooms', ['admin_id' => $admin->id, 'room_id' => $response->json('data.id')]);
    }

    /**
     * The room detail page's update feature must be able to change name, slug, status and the
     * assigned admins together, and unassign an admin previously assigned to the room.
     *
     * @return void
     */
    public function test_superadmin_can_update_room_details_and_reassign_admins(): void
    {
        $root = $this->superadmin();
        $oldAdmin = AdminAccount::create(['name' => 'Old Lead', 'email' => 'old-lead@drinkflow.test', 'password' => 'password123', 'role' => AdminRole::Admin, 'status' => 'active']);
        $newAdmin = AdminAccount::create(['name' => 'New Lead', 'email' => 'new-lead@drinkflow.test', 'password' => 'password123', 'role' => AdminRole::Admin, 'status' => 'active']);
        $room = Room::create(['name' => 'Support', 'slug' => 'support', 'status' => 'active']);
        $room->admins()->sync([$oldAdmin->id]);

        $response = $this->actingAs($root, 'admin')->patchJson("/superadmin/rooms/{$room->id}", [
            'name' => 'Customer Support', 'slug' => 'customer-support', 'status' => 'active', 'admin_ids' => [$newAdmin->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Customer Support')
            ->assertJsonPath('data.slug', 'customer-support')
            ->assertJsonCount(1, 'data.admins')
            ->assertJsonPath('data.admins.0.id', $newAdmin->id);
        $this->assertDatabaseHas('admin_rooms', ['admin_id' => $newAdmin->id, 'room_id' => $room->id]);
        $this->assertDatabaseMissing('admin_rooms', ['admin_id' => $oldAdmin->id, 'room_id' => $room->id]);
    }

    /**
     * The room status endpoint previously only accepted 'active'/'disabled'/'archived', but
     * RoomStatus::class only backs 'active'/'inactive'/'archived' — meaning "disable room" would
     * throw an uncaught enum ValueError. Confirm the room can actually be set to inactive now.
     *
     * @return void
     */
    public function test_superadmin_can_set_room_status_to_inactive(): void
    {
        $root = $this->superadmin();
        $room = Room::create(['name' => 'Finance', 'slug' => 'finance', 'status' => 'active']);

        $this->actingAs($root, 'admin')->patchJson("/superadmin/rooms/{$room->id}/status", ['status' => 'inactive'])
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'status' => 'inactive']);
    }

    public function test_last_superadmin_cannot_be_demoted_or_blocked(): void
    {
        $root = $this->superadmin();
        $this->actingAs($root, 'admin')->patchJson("/superadmin/admins/{$root->id}/role", ['role' => 'admin'])->assertStatus(422);
        $this->actingAs($root, 'admin')->patchJson("/superadmin/admins/{$root->id}/status", ['status' => 'blocked'])->assertStatus(422);
        $this->assertDatabaseHas('admin_accounts', ['id' => $root->id, 'role' => 'superadmin', 'status' => 'active']);
    }

    public function test_global_user_detail_exposes_identity_metadata_without_credentials(): void
    {
        $root = $this->superadmin();
        $user = GlobalUser::create(['name' => 'User', 'normalized_name' => 'USER', 'email' => 'user@drinkflow.test']);
        OAuthIdentity::create(['global_user_id' => $user->id, 'provider' => 'google', 'provider_user_id' => 'google-1', 'provider_email' => $user->email]);

        $this->actingAs($root, 'admin')->getJson("/superadmin/global-users/{$user->id}")
            ->assertOk()->assertJsonPath('data.oauth_identities.0.provider_user_id', 'google-1')->assertJsonMissing(['access_token' => 'secret']);
    }

    public function test_secret_system_setting_is_saved_encrypted_and_never_returned(): void
    {
        $root = $this->superadmin();
        $this->actingAs($root, 'admin')->putJson('/superadmin/system/settings', ['settings' => [['key' => 'oauth.client_secret', 'value' => 'top-secret', 'type' => 'string', 'is_secret' => true]]])->assertOk();
        $this->assertDatabaseMissing('system_settings', ['value' => 'top-secret']);
        $this->actingAs($root, 'admin')->getJson('/superadmin/system')->assertOk()->assertJsonPath('data.settings.0.value', null);
    }

    public function test_typed_non_secret_system_setting_can_be_updated(): void
    {
        $root = $this->superadmin();

        $this->actingAs($root, 'admin')->putJson('/superadmin/system/settings', ['settings' => [
            ['key' => 'orders.daily_limit', 'value' => 25, 'type' => 'integer', 'is_secret' => false],
            ['key' => 'orders.allow_cash', 'value' => true, 'type' => 'boolean', 'is_secret' => false],
        ]])->assertOk();

        $this->actingAs($root, 'admin')->getJson('/superadmin/system')->assertOk()
            ->assertJsonPath('data.settings.0.key', 'orders.allow_cash')
            ->assertJsonPath('data.settings.0.value', true)
            ->assertJsonPath('data.settings.1.value', 25);
    }

    public function test_maintenance_schedule_is_saved_and_returned_to_superadmin(): void
    {
        $root = $this->superadmin();
        $payload = ['enabled' => true, 'starts_at' => '2026-09-12 08:30:00', 'ends_at' => '2026-09-12 12:00:00'];

        $this->actingAs($root, 'admin')->putJson('/superadmin/system/maintenance', $payload)
            ->assertOk()
            ->assertJsonPath('data.starts_at', $payload['starts_at'])
            ->assertJsonPath('data.ends_at', $payload['ends_at']);

        $this->actingAs($root, 'admin')->getJson('/superadmin/system')
            ->assertOk()
            ->assertJsonPath('data.maintenance.starts_at', $payload['starts_at'])
            ->assertJsonPath('data.maintenance.ends_at', $payload['ends_at']);
    }

    public function test_system_reset_requires_exact_confirmation_and_preserves_superadmin(): void
    {
        $root = $this->superadmin();
        $room = Room::create(['name' => 'IT', 'slug' => 'reset-it']);
        $this->actingAs($root, 'admin')->postJson('/superadmin/system/reset', ['password' => 'password123', 'phrase' => 'reset drinkflow'])->assertStatus(422);
        $this->assertDatabaseHas('rooms', ['id' => $room->id]);

        $this->actingAs($root, 'admin')->postJson('/superadmin/system/reset', ['password' => 'password123', 'phrase' => 'RESET DRINKFLOW'])->assertOk();
        $this->assertDatabaseHas('admin_accounts', ['id' => $root->id, 'role' => 'superadmin']);
        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    public function test_superadmin_can_block_and_unblock_admin(): void
    {
        $root = $this->superadmin();
        $admin = AdminAccount::create(['name' => 'Operator', 'email' => 'operator-block@drinkflow.test', 'password' => 'password123', 'role' => AdminRole::Admin, 'status' => 'active']);

        $this->actingAs($root, 'admin')->patchJson("/superadmin/admins/{$admin->id}/status", ['status' => 'blocked'])
            ->assertOk()
            ->assertJsonPath('data.status', 'blocked');
        $this->assertDatabaseHas('admin_accounts', ['id' => $admin->id, 'status' => 'blocked']);

        $this->actingAs($root, 'admin')->get('/superadmin/admins/page?status=blocked')
            ->assertOk()
            ->assertSee('operator-block@drinkflow.test')
            ->assertSee('status-pill status-blocked', false);

        $this->actingAs($root, 'admin')->patchJson("/superadmin/admins/{$admin->id}/status", ['status' => 'unknown'])->assertStatus(422);

        $this->actingAs($root, 'admin')->patchJson("/superadmin/admins/{$admin->id}/status", ['status' => 'active'])
            ->assertOk()
            ->assertJsonPath('data.status', 'active');
    }

    public function test_admin_management_page_uses_modals_and_block_only_actions(): void
    {
        $root = $this->superadmin();
        $admin = AdminAccount::create(['name' => 'Operator', 'email' => 'operator-page@drinkflow.test', 'password' => 'password123', 'role' => AdminRole::Admin, 'status' => 'active']);

        $this->actingAs($root, 'admin')->get('/superadmin/admins/page')
            ->assertOk()
            ->assertSee('id="create-admin-modal"', false)
            ->assertSee('data-password-toggle="create-admin-password"', false)
            ->assertSee('data-password-toggle="create-admin-password-confirmation"', false)
            ->assertSee('data-action="block-admin" data-admin-id="'.$admin->id.'"', false)
            ->assertDontSee('data-admin-id="'.$root->id.'"', false)
            ->assertDontSee(__('superadmin.common.unblock'));

        $this->actingAs($root, 'admin')->get("/superadmin/admins/{$admin->id}/page")
            ->assertOk()
            ->assertSee('id="room-access-modal"', false)
            ->assertSee('id="reset-password-modal"', false)
            ->assertSee('data-password-toggle="reset-password"', false)
            ->assertSee('data-password-toggle="reset-password-confirmation"', false)
            ->assertDontSee('id="room-assignment-panel"', false)
            ->assertSee(__('superadmin.common.loading_title'))
            ->assertSee('id="tpl-no-assigned-rooms"', false)
            ->assertSee(__('superadmin.admins.no_assigned_rooms_description'));

        $this->actingAs($root, 'admin')->get('/superadmin/admins/page?q=no-such-admin')
            ->assertOk()
            ->assertSee(__('superadmin.admins.no_results_title'))
            ->assertSee(__('superadmin.admins.no_results_description'));
    }

    public function test_global_user_pages_render_empty_states_and_distinct_block_button(): void
    {
        $root = $this->superadmin();
        $user = GlobalUser::create(['name' => 'Alice', 'normalized_name' => 'ALICE', 'email' => 'alice-empty@drinkflow.test', 'status' => 'active']);

        $this->actingAs($root, 'admin')->get('/superadmin/global-users/page')
            ->assertOk()
            ->assertSee('class="sa-button warning" type="button" data-action="toggle-user-status" data-user-id="'.$user->id.'"', false);

        $this->actingAs($root, 'admin')->get('/superadmin/global-users/page?q=no-such-user')
            ->assertOk()
            ->assertSee(__('superadmin.users.no_results_title'))
            ->assertSee(__('superadmin.users.no_results_description'));

        $this->actingAs($root, 'admin')->get("/superadmin/global-users/{$user->id}/page")
            ->assertOk()
            ->assertSee(__('superadmin.common.loading_title'))
            ->assertSee('id="tpl-no-memberships"', false)
            ->assertSee('id="tpl-load-failed"', false);
    }

    public function test_campaign_registry_filters_by_room_and_shows_actions_by_status(): void
    {
        $root = $this->superadmin();
        $roomA = Room::create(['name' => 'Room A', 'slug' => 'room-a']);
        $roomB = Room::create(['name' => 'Room B', 'slug' => 'room-b']);
        $active = Campaign::create(['room_id' => $roomA->id, 'status' => CampaignStatus::Active, 'name' => 'Active lunch', 'restaurant' => 'Shop', 'type' => 'food', 'deadline_at' => now()->addHour()]);
        $draft = Campaign::create(['room_id' => $roomB->id, 'status' => CampaignStatus::Draft, 'name' => 'Draft tea', 'restaurant' => 'Shop', 'type' => 'drink', 'deadline_at' => now()->addHour()]);

        $this->actingAs($root, 'admin')->get('/superadmin/campaigns/page')
            ->assertOk()
            ->assertSee('data-action="force-close" data-campaign-id="'.$active->id.'"', false)
            ->assertSee('data-action="force-cancel" data-campaign-id="'.$draft->id.'"', false)
            ->assertDontSee('data-action="force-close" data-campaign-id="'.$draft->id.'"', false)
            ->assertSee('id="confirm-modal-description"', false);

        $this->actingAs($root, 'admin')->get('/superadmin/campaigns/page?room_id='.$roomB->id)
            ->assertOk()
            ->assertSee('Draft tea')
            ->assertDontSee('Active lunch');

        $this->actingAs($root, 'admin')->get('/superadmin/campaigns/page?room_id='.$roomA->id.'&status=cancelled')
            ->assertOk()
            ->assertSee(__('superadmin.campaigns.no_results_title'));
    }

    public function test_audit_page_lists_only_admin_actor_logs_with_translated_events(): void
    {
        $root = $this->superadmin();
        $admin = AdminAccount::create(['name' => 'Operator', 'email' => 'operator-audit@drinkflow.test', 'password' => 'password123', 'role' => AdminRole::Admin, 'status' => 'active']);

        $this->actingAs($root, 'admin')->get('/superadmin/audit-logs/page')
            ->assertOk()->assertSee(__('superadmin.audit.no_events_title'));

        $log = fn (string $actorType, ?int $actorId, string $event) => AuditLog::create(['actor_type' => $actorType, 'actor_id' => $actorId, 'event' => $event, 'target_type' => 'campaign', 'target_id' => 1, 'created_at' => now()]);
        $log('superadmin', $root->id, 'campaign.force_closed');
        $log('admin', $admin->id, 'room.settings_updated');
        $log('user', 99, 'user.logged_in');
        $log('system', null, 'system.reset');

        $this->actingAs($root, 'admin')->get('/superadmin/audit-logs/page')
            ->assertOk()
            ->assertSee(__('admin.audit_event_campaign_force_closed'))
            ->assertSee(__('admin.audit_event_room_settings_updated'))
            ->assertSee('Operator')
            ->assertSee(__('admin.audit_target_campaign'))
            ->assertDontSee('user.logged_in')
            ->assertDontSee('system.reset');

        // An actor type outside admin/superadmin is ignored instead of exposing end-user logs.
        $this->actingAs($root, 'admin')->get('/superadmin/audit-logs/page?actor_type=user')
            ->assertOk()->assertDontSee('user.logged_in')->assertSee('campaign.force_closed');

        $this->actingAs($root, 'admin')->get('/superadmin/audit-logs/page?actor_type=admin&event=campaign.force_closed')
            ->assertOk()->assertSee(__('superadmin.audit.no_results_title'));
    }

    public function test_system_page_uses_reset_modal_and_empty_states(): void
    {
        $root = $this->superadmin();

        $this->actingAs($root, 'admin')->get('/superadmin/system/page')
            ->assertOk()
            ->assertSee('id="reset-system-modal"', false)
            ->assertSee('data-modal-open="reset-system-modal"', false)
            ->assertSee('id="maintenance-form" class="sa-health-list" data-no-loading', false)
            ->assertSee(__('superadmin.system.reset_phrase_label', ['phrase' => \App\Actions\Superadmin\ResetSystemAction::CONFIRMATION_PHRASE]))
            ->assertDontSee('id="settings-form"', false)
            ->assertSee(__('superadmin.common.loading_title'))
            ->assertDontSee('prompt(', false);
    }

    public function test_superadmin_page_routes_render_for_active_superadmin(): void
    {
        $root = $this->superadmin();

        foreach ([
            '/superadmin',
            '/superadmin/rooms/page',
            '/superadmin/rooms/1/page',
            '/superadmin/admins/page',
            '/superadmin/admins/1/page',
            '/superadmin/global-users/page',
            '/superadmin/global-users/1/page',
            '/superadmin/campaigns/page',
            '/superadmin/notifications/page',
            '/superadmin/system/page',
            '/superadmin/audit-logs/page',
            '/superadmin/security-events/page',
            '/superadmin/socket/page',
            '/superadmin/queue/page',
            '/superadmin/versions/page',
        ] as $uri) {
            $this->actingAs($root, 'admin')->get($uri)->assertOk();
        }
    }
}
