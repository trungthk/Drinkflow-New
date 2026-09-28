<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\GlobalUserStatus;
use App\Enums\RoomStatus;
use App\Models\AdminAccount;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomSetting;
use App\Services\Room\RoomAccessPolicy;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomAccessRulesTest extends TestCase
{
    use RefreshDatabase;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->room = Room::create(['name' => 'Access Room', 'slug' => 'access-room', 'status' => RoomStatus::Active]);
    }

    private function admin(): AdminAccount
    {
        $admin = AdminAccount::create(['name' => 'Room Admin', 'email' => 'access-admin@example.test', 'password' => 'secret123', 'role' => AdminRole::Admin, 'status' => 'active']);
        $admin->rooms()->attach($this->room);

        return $admin;
    }

    private function user(string $email = 'member@thk-hd.vn'): GlobalUser
    {
        return GlobalUser::create(['name' => 'Member', 'normalized_name' => 'MEMBER', 'email' => $email, 'status' => GlobalUserStatus::Active]);
    }

    /**
     * @param array<string, list<string>> $lists
     */
    private function rules(array $lists): void
    {
        foreach ($lists as $key => $values) {
            RoomSetting::updateOrCreate(['room_id' => $this->room->id, 'key' => $key], ['value' => json_encode($values), 'type' => RoomSetting::TYPE_JSON]);
        }
    }

    public function test_admin_saves_normalized_access_lists(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin')
            ->patchJson(route('admin.settings.update', $this->room), [
                'allowed_email_domains' => ['THK-HD.vn', '@drinkflow.com', 'thk-hd.vn', ' '],
                'allowed_ips' => ['127.0.0.1', '0:0:0:0:0:0:0:1'],
                'blocked_ips' => ['2001:db8::1'],
            ])
            ->assertOk()
            ->assertJsonPath('data.allowed_email_domains', ['thk-hd.vn', 'drinkflow.com'])
            ->assertJsonPath('data.allowed_ips', ['127.0.0.1', '::1'])
            ->assertJsonPath('data.blocked_ips', ['2001:db8::1']);

        $this->actingAs($admin, 'admin')
            ->getJson(route('admin.settings.show', $this->room))
            ->assertJsonPath('data.allowed_email_domains', ['thk-hd.vn', 'drinkflow.com']);

        // Clearing a list removes the restriction.
        $this->patchJson(route('admin.settings.update', $this->room), ['allowed_ips' => []])
            ->assertOk()
            ->assertJsonPath('data.allowed_ips', []);
    }

    public function test_invalid_domains_and_ips_are_rejected_with_a_readable_message(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->patchJson(route('admin.settings.update', $this->room), ['allowed_ips' => ['127.0.0.1', '999.1.1.1']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['allowed_ips.1'])
            ->assertJsonFragment([__('admin.room_access_invalid_ip', ['value' => '999.1.1.1'])]);

        $this->actingAs($admin, 'admin')
            ->patchJson(route('admin.settings.update', $this->room), ['allowed_email_domains' => ['not a domain']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['allowed_email_domains.0']);

        $this->actingAs($admin, 'admin')
            ->patchJson(route('admin.settings.update', $this->room), ['blocked_ips' => array_map(static fn (int $i): string => "10.0.0.{$i}", range(1, 101))])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['blocked_ips']);
    }

    public function test_blocked_ip_cannot_open_room_pages(): void
    {
        $this->rules([RoomAccessPolicy::BLOCKED_IPS => ['10.0.0.9']]);
        $user = $this->user();

        $this->actingAs($user, 'web')->withServerVariables(['REMOTE_ADDR' => '10.0.0.9'])
            ->get(route('user.dashboard', $this->room->slug))
            ->assertForbidden()
            ->assertSee(__('room.access.ip_denied', ['ip' => '10.0.0.9']));

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.9'])
            ->getJson(route('user.campaigns.index', $this->room->slug))
            ->assertForbidden()
            ->assertJsonPath('message', __('room.access.ip_denied', ['ip' => '10.0.0.9']));

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.9'])
            ->post(route('user.rooms.join', $this->room->slug))
            ->assertForbidden();
        $this->assertSame(0, $this->room->roomUsers()->count());

        // Another IP is not affected by the block list.
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.10'])
            ->get(route('user.rooms.join.show', $this->room->slug))
            ->assertOk();
    }

    public function test_allow_list_admits_only_listed_ips_and_block_list_wins(): void
    {
        $this->rules([
            RoomAccessPolicy::ALLOWED_IPS => ['192.168.1.10', '::1'],
            RoomAccessPolicy::BLOCKED_IPS => ['192.168.1.10'],
        ]);
        $user = $this->user();

        $this->actingAs($user, 'web')->withServerVariables(['REMOTE_ADDR' => '192.168.1.20'])
            ->get(route('user.rooms.join.show', $this->room->slug))
            ->assertForbidden();

        // Listed, but also blocked: the block list wins.
        $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.10'])
            ->get(route('user.rooms.join.show', $this->room->slug))
            ->assertForbidden();

        // IPv6 is compared in binary form, so the long notation of ::1 matches too.
        $this->withServerVariables(['REMOTE_ADDR' => '0:0:0:0:0:0:0:1'])
            ->get(route('user.rooms.join.show', $this->room->slug))
            ->assertOk();
    }

    public function test_only_allowed_email_domains_can_join(): void
    {
        $this->rules([RoomAccessPolicy::ALLOWED_EMAIL_DOMAINS => ['thk-hd.vn', 'drinkflow.com']]);

        $outsider = $this->user('guest@gmail.com');
        $this->actingAs($outsider, 'web')
            ->get(route('user.rooms.join.show', $this->room->slug))
            ->assertForbidden()
            ->assertSee('@thk-hd.vn, @drinkflow.com');
        $this->post(route('user.rooms.join', $this->room->slug))->assertForbidden();
        $this->assertSame(0, $this->room->roomUsers()->count());

        // Joining by room link reports the rule on the form.
        $this->from('/me/rooms')
            ->post('/me/rooms/join', ['room_url' => 'https://drinkflow.vn/rooms/access-room'])
            ->assertRedirect('/me/rooms')
            ->assertSessionHasErrors(['room_url' => app(RoomAccessPolicy::class)->emailDeniedMessage($this->room)]);

        $member = GlobalUser::create(['name' => 'Staff', 'normalized_name' => 'STAFF', 'email' => 'Staff@THK-HD.vn', 'status' => GlobalUserStatus::Active]);
        $this->actingAs($member, 'web')
            ->post(route('user.rooms.join', $this->room->slug))
            ->assertRedirect(route('user.dashboard', $this->room->slug));
        $this->assertSame(1, $this->room->roomUsers()->count());
    }

    public function test_rooms_without_rules_are_unrestricted(): void
    {
        $this->actingAs($this->user('anyone@example.org'), 'web')
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
            ->post(route('user.rooms.join', $this->room->slug))
            ->assertRedirect(route('user.dashboard', $this->room->slug));
    }

    public function test_settings_page_renders_the_access_rule_inputs(): void
    {
        $this->rules([RoomAccessPolicy::BLOCKED_IPS => ['10.0.0.9']]);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.settings.page', $this->room))
            ->assertOk()
            ->assertSee('data-tags-input="allowed_email_domains"', false)
            ->assertSee('data-tags-input="allowed_ips"', false)
            ->assertSee('data-tags-input="blocked_ips"', false)
            ->assertSee('data-tag="10.0.0.9"', false);
    }
}
