<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\GlobalUserStatus;
use App\Enums\RoomStatus;
use App\Enums\RoomUserStatus;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use App\Services\Code\CodeGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserCodeUuidTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test global user automatically gets a unique UUID code when created.
     */
    public function test_global_user_auto_generates_uuid_code_on_creation(): void
    {
        $user = GlobalUser::create([
            'name'            => 'Test Global User',
            'normalized_name' => 'test global user',
            'email'           => 'global-user-code@example.test',
            'status'          => GlobalUserStatus::Active,
        ]);

        $this->assertNotNull($user->code);
        $this->assertTrue(Str::isUuid($user->code));
        $this->assertDatabaseHas('global_users', [
            'id'   => $user->id,
            'code' => $user->code,
        ]);
    }

    /**
     * Test room user automatically gets a unique UUID user_code when created.
     */
    public function test_room_user_auto_generates_uuid_user_code_on_creation(): void
    {
        $user = GlobalUser::create([
            'name'            => 'Room Member',
            'normalized_name' => 'room member',
            'email'           => 'room-member@example.test',
            'status'          => GlobalUserStatus::Active,
        ]);

        $room = Room::create([
            'name'   => 'Room Test Code',
            'slug'   => 'room-test-code',
            'status' => RoomStatus::Active,
        ]);

        $roomUser = RoomUser::create([
            'room_id'         => $room->id,
            'global_user_id'  => $user->id,
            'display_name'    => $user->name,
            'normalized_name' => $user->normalized_name,
            'status'          => RoomUserStatus::Active,
        ]);

        $this->assertNotNull($roomUser->user_code);
        $this->assertTrue(Str::isUuid($roomUser->user_code));
        $this->assertDatabaseHas('room_users', [
            'id'        => $roomUser->id,
            'user_code' => $roomUser->user_code,
        ]);
    }

    /**
     * Test code uniqueness constraint across models.
     */
    public function test_codes_generated_by_service_are_unique_uuids(): void
    {
        $globalCode = CodeGeneratorService::generateGlobalUserCode();
        $roomUserCode = CodeGeneratorService::generateRoomUserCode();

        $this->assertTrue(Str::isUuid($globalCode));
        $this->assertTrue(Str::isUuid($roomUserCode));
        $this->assertNotSame($globalCode, $roomUserCode);
    }
}
