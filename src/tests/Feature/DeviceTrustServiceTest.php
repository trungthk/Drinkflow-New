<?php
namespace Tests\Feature;
use App\Actions\User\JoinRoomAction;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Services\Auth\DeviceTrustService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class DeviceTrustServiceTest extends TestCase {
    use RefreshDatabase;
    public function test_issued_token_resolves_and_revoke_invalidates_it(): void {
        $user = GlobalUser::create(['name' => 'A', 'normalized_name' => 'A', 'email' => 'a@company.com']);
        $room = Room::create(['name' => 'IT', 'slug' => 'device-it']);
        $roomUser = app(JoinRoomAction::class)->execute($user, $room, 'device', 'legacy-hash');
        $service = app(DeviceTrustService::class);
        $token = $service->issue($roomUser, 'device');
        $device = $service->resolve('device', $token, $room->id);
        $this->assertNotNull($device);
        $this->assertNull($service->resolve('device', 'wrong-token', $room->id));
        $service->revoke($device);
        $this->assertNull($service->resolve('device', $token, $room->id));
        $this->assertNotSame($token, $device->token_hash);
    }
}
