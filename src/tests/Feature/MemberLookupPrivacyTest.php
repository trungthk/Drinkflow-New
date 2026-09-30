<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\User\JoinRoomAction;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use App\Support\Helpers\FormatHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UPG-03.2: the member lookup used to order on behalf returns masked contact details and is rate limited.
 */
class MemberLookupPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private Room $room;

    private GlobalUser $requester;

    private RoomUser $colleague;

    protected function setUp(): void
    {
        parent::setUp();
        $this->room = Room::create(['name' => 'Lookup', 'slug' => 'lookup-room']);
        $this->requester = GlobalUser::create(['name' => 'Requester', 'normalized_name' => 'REQUESTER', 'email' => 'requester@company.com']);
        $other = GlobalUser::create(['name' => 'Colleague', 'normalized_name' => 'COLLEAGUE', 'email' => 'nguyen.van.a@company.com', 'phone' => '0901234123']);
        $join = app(JoinRoomAction::class);
        $join->execute($this->requester, $this->room, 'device-r', 'hash-r');
        $this->colleague = $join->execute($other, $this->room, 'device-c', 'hash-c');
    }

    public function test_lookup_returns_masked_contact_details_only(): void
    {
        $response = $this->actingAs($this->requester, 'web')
            ->getJson(route('user.room-members.lookup', ['room' => $this->room, 'q' => 'nguyen.van.a@company.com']))
            ->assertOk()
            ->assertJsonPath('user_code', $this->colleague->user_code)
            ->assertJsonPath('email', 'n***@company.com')
            ->assertJsonPath('phone', '09****123');

        $this->assertStringNotContainsString('nguyen.van.a', $response->getContent());
        $this->assertStringNotContainsString('0901234123', $response->getContent());
    }

    public function test_lookup_is_rate_limited_per_member(): void
    {
        $url = route('user.room-members.lookup', ['room' => $this->room, 'q' => $this->colleague->user_code]);
        for ($i = 0; $i < 20; $i++) {
            $this->actingAs($this->requester, 'web')->getJson($url)->assertOk();
        }

        $this->actingAs($this->requester, 'web')->getJson($url)->assertStatus(429);
    }

    public function test_mask_helpers(): void
    {
        $this->assertSame('a***@x.io', FormatHelper::maskEmail('alice@x.io'));
        $this->assertNull(FormatHelper::maskEmail(null));
        $this->assertSame('09****789', FormatHelper::maskPhone('09 1234 5789'));
        $this->assertSame('***', FormatHelper::maskPhone('123'));
        $this->assertNull(FormatHelper::maskPhone(''));
    }
}
