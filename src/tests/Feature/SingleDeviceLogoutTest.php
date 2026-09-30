<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\User\JoinRoomAction;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUserDevice;
use App\Services\User\UserSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\DatabaseSessionHandler;
use Illuminate\Session\EncryptedStore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * UPG-02.2: "sign out this device" revokes exactly the device of the selected session (option a:
 * the remember token is rotated, other room-joined devices restore their session from their own
 * trusted device token).
 */
class SingleDeviceLogoutTest extends TestCase
{
    use RefreshDatabase;

    private GlobalUser $user;

    protected function setUp(): void
    {
        parent::setUp();
        config(['session.driver' => 'database', 'session.encrypt' => true]);
        $this->user = GlobalUser::create(['name' => 'Three Devices', 'normalized_name' => 'THREE DEVICES', 'email' => 'three@company.com', 'status' => 'active']);
        $this->user->forceFill(['remember_token' => 'old-token'])->save();
        $room = Room::create(['name' => 'Room', 'slug' => 'device-room']);
        foreach ([1, 2, 3] as $n) {
            app(JoinRoomAction::class)->execute($this->user, $room, "device-{$n}", hash('sha256', "token-{$n}"));
        }
    }

    /**
     * Store a database session opened from a trusted device, as ResolveGlobalUser records it.
     *
     * @param string $sessionId Session ID.
     * @param string $deviceUuid Device UUID stored in the session.
     * @return void
     */
    private function sessionFor(string $sessionId, string $deviceUuid): void
    {
        $handler = new DatabaseSessionHandler(DB::connection(), 'sessions', 120, $this->app);
        $store = new EncryptedStore((string) config('session.cookie'), $handler, $this->app['encrypter'], $sessionId);
        $store->start();
        $store->put(UserSessionService::SESSION_DEVICE_KEY, $deviceUuid);
        $store->save();
        DB::table('sessions')->where('id', $sessionId)->update(['user_id' => $this->user->id]);
    }

    private function revoked(string $deviceUuid): bool
    {
        return RoomUserDevice::query()->where('device_uuid', $deviceUuid)->whereNotNull('revoked_at')->exists();
    }

    public function test_only_the_selected_device_is_revoked(): void
    {
        $sessions = [];
        foreach ([1, 2, 3] as $n) {
            $sessions[$n] = str_repeat((string) $n, 40);
            $this->sessionFor($sessions[$n], "device-{$n}");
        }
        $service = app(UserSessionService::class);
        $this->assertSame('device-2', $service->sessionDeviceUuid($this->user, $sessions[2]));

        $service->logoutDevice($this->user, $sessions[2], 'device-1');

        $this->assertTrue($this->revoked('device-2'));
        $this->assertFalse($this->revoked('device-1'));
        $this->assertFalse($this->revoked('device-3'));
        $this->assertFalse(DB::table('sessions')->where('id', $sessions[2])->exists());
        $this->assertTrue(DB::table('sessions')->where('id', $sessions[3])->exists());
        $this->assertNotSame('old-token', $this->user->fresh()->getRememberToken());
    }

    public function test_other_devices_stay_signed_in_and_the_revoked_one_does_not(): void
    {
        $session = str_repeat('2', 40);
        $this->sessionFor($session, 'device-2');
        app(UserSessionService::class)->logoutDevice($this->user, $session, 'device-1');
        config(['session.driver' => 'array']);

        foreach ([3 => true, 2 => false] as $n => $expected) {
            $this->app['auth']->forgetGuards();
            $this->flushSession();
            $this->withCookies(['drinkflow_device_uuid' => "device-{$n}", 'drinkflow_trusted_token' => "token-{$n}"])
                ->get(route('user.me.dashboard'));
            $this->assertSame($expected, Auth::guard('web')->check(), "device-{$n}");
        }
    }

    public function test_session_of_another_user_is_ignored(): void
    {
        $session = str_repeat('9', 40);
        $this->sessionFor($session, 'device-3');
        $stranger = GlobalUser::create(['name' => 'Stranger', 'normalized_name' => 'STRANGER', 'email' => 'stranger@company.com', 'status' => 'active']);

        app(UserSessionService::class)->logoutDevice($stranger, $session, '');

        $this->assertFalse($this->revoked('device-3'));
        $this->assertTrue(DB::table('sessions')->where('id', $session)->exists());
    }

    public function test_middleware_records_the_device_in_the_session(): void
    {
        config(['session.driver' => 'array']);

        $this->withCookies(['drinkflow_device_uuid' => 'device-1', 'drinkflow_trusted_token' => 'token-1'])
            ->get(route('user.me.dashboard'))
            ->assertSessionHas(UserSessionService::SESSION_DEVICE_KEY, 'device-1');
    }
}
