<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Events\ForceReloadRequested;
use App\Events\MaintenanceStateChanged;
use App\Listeners\PublishRealtimeEvent;
use App\Models\AdminAccount;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use App\Models\RoomUserDevice;
use App\Services\Auth\DeviceTrustService;
use App\Services\Realtime\SocketTokenService;
use App\Services\System\SystemSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Realtime events that force open pages to reload: revoked devices, deleted accounts and maintenance. */
class RealtimeForceReloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_revoking_a_trusted_device_forces_that_device_to_reload(): void
    {
        Event::fake([ForceReloadRequested::class]);
        [, $roomUser] = $this->member();
        $device = RoomUserDevice::create(['room_user_id' => $roomUser->id, 'device_uuid' => 'device-abc', 'token_hash' => 'hash']);

        app(DeviceTrustService::class)->revoke($device);

        $this->assertNotNull($device->fresh()->revoked_at);
        Event::assertDispatched(ForceReloadRequested::class, fn (ForceReloadRequested $event): bool => $event->channel === 'device:device-abc'
            && $event->reason === ForceReloadRequested::REASON_DEVICE_REVOKED);
    }

    public function test_deleting_a_global_user_forces_all_their_pages_to_reload(): void
    {
        Event::fake([ForceReloadRequested::class]);
        [$user] = $this->member();
        $userId = $user->id;

        $this->actingAs($this->superadmin(), 'admin')->deleteJson("/superadmin/global-users/{$userId}")->assertOk();

        // Soft delete: the row stays (order/debt history) but the account is marked deleted.
        $this->assertDatabaseHas('global_users', ['id' => $userId, 'status' => 'deleted']);
        Event::assertDispatched(ForceReloadRequested::class, fn (ForceReloadRequested $event): bool => $event->channel === 'global_user:'.$userId
            && $event->reason === ForceReloadRequested::REASON_ACCOUNT_DELETED);
    }

    public function test_updating_maintenance_announces_the_new_state(): void
    {
        Event::fake([MaintenanceStateChanged::class]);

        $this->actingAs($this->superadmin(), 'admin')
            ->putJson('/superadmin/system/maintenance', ['enabled' => true])
            ->assertOk();

        Event::assertDispatched(MaintenanceStateChanged::class);
    }

    public function test_force_reload_is_published_privately_to_the_target_channel(): void
    {
        $this->fakeRealtime();

        app(PublishRealtimeEvent::class)->handle(ForceReloadRequested::forDevice('device-abc', ForceReloadRequested::REASON_DEVICE_REVOKED));

        Http::assertSent(fn ($request): bool => $request['event'] === 'session.force_reload'
            && $request['room_id'] === 0
            && $request['user_channel'] === 'device:device-abc'
            && $request['payload'] === ['reason' => ForceReloadRequested::REASON_DEVICE_REVOKED]);
    }

    public function test_maintenance_payload_reports_active_and_scheduled_states(): void
    {
        $this->fakeRealtime();
        $settings = app(SystemSettingsService::class);

        $settings->set('maintenance.enabled', true, 'boolean');
        app(PublishRealtimeEvent::class)->handle(new MaintenanceStateChanged());

        $settings->set('maintenance.starts_at', now()->addMinutes(10)->format('Y-m-d\TH:i'), 'string');
        app(PublishRealtimeEvent::class)->handle(new MaintenanceStateChanged());

        Http::assertSent(fn ($request): bool => $request['event'] === 'system.maintenance'
            && $request['user_channel'] === null
            && $request['payload']['active'] === true
            && $request['payload']['starts_in'] === null);
        Http::assertSent(fn ($request): bool => $request['event'] === 'system.maintenance'
            && $request['payload']['active'] === false
            && $request['payload']['scheduled'] === true
            && $request['payload']['starts_in'] > 0
            && $request['payload']['starts_in'] <= 600);
    }

    public function test_room_socket_token_carries_a_valid_device_uuid_only(): void
    {
        [, $roomUser] = $this->member();
        $tokens = app(SocketTokenService::class);

        $this->assertSame('device-abc', $tokens->verify($tokens->issue($roomUser, deviceUuid: 'device-abc'))['device_uuid'] ?? null);
        $this->assertArrayNotHasKey('device_uuid', $tokens->verify($tokens->issue($roomUser, deviceUuid: 'bad uuid!')));
        $this->assertArrayNotHasKey('device_uuid', $tokens->verify($tokens->issue($roomUser)));
    }

    /** @return array{0: GlobalUser, 1: RoomUser} */
    private function member(): array
    {
        $user = GlobalUser::create(['name' => 'Member', 'normalized_name' => 'MEMBER', 'email' => 'member-reload@example.com', 'status' => 'active']);
        $room = Room::create(['name' => 'Reload', 'slug' => 'reload']);
        $roomUser = RoomUser::create(['room_id' => $room->id, 'global_user_id' => $user->id, 'user_code' => 'RL-001', 'display_name' => 'Member', 'normalized_name' => 'MEMBER', 'status' => 'active']);

        return [$user, $roomUser];
    }

    private function superadmin(): AdminAccount
    {
        return AdminAccount::create(['name' => 'Root', 'email' => 'root-reload@drinkflow.test', 'password' => 'password123', 'role' => AdminRole::SuperAdmin, 'status' => 'active']);
    }

    private function fakeRealtime(): void
    {
        config()->set('services.realtime.url', 'http://realtime.test');
        config()->set('services.realtime.internal_secret', 'test-secret');
        Http::fake(['http://realtime.test/internal/emit' => Http::response(['delivered' => true], 202)]);
    }
}
