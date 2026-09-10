<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Models\AdminAccount;
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
            '/superadmin/debts/page',
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
