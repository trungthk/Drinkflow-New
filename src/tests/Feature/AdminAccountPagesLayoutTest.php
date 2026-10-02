<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Package;
use App\Models\Room;
use App\Services\Subscription\SubscriptionService;
use App\View\Composers\AdminLayoutComposer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Account pages outside a room (my rooms, subscription, billing) use the same admin shell as the room
 * pages: the sidebar menu of the room the Agent operated last, or of its first room.
 */
class AdminAccountPagesLayoutTest extends TestCase
{
    use RefreshDatabase;

    private Admin $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agent = Admin::create(['name' => 'Agent', 'email' => 'agent@drinkflow.test', 'password' => 'password123', 'status' => 'active']);
        $package = Package::create(['code' => 'plan', 'name' => 'Plan', 'monthly_price' => 100000, 'room_limit' => 5, 'status' => 'active']);
        app(SubscriptionService::class)->activate($this->agent, $package);
    }

    private function room(string $slug, ?Admin $owner = null): Room
    {
        $owner ??= $this->agent;
        $room = Room::create(['name' => 'Room '.$slug, 'slug' => $slug, 'status' => 'active', 'owner_admin_id' => $owner->id]);
        $room->admins()->attach($owner->id);

        return $room;
    }

    /**
     * @return array<int, string> Account page routes.
     */
    private function accountPages(): array
    {
        return [route('admin.rooms.index'), route('admin.subscription.show'), route('admin.billing.index')];
    }

    public function test_account_pages_show_the_room_menu_of_the_first_room(): void
    {
        $alpha = $this->room('alpha');
        $this->room('beta');

        foreach ($this->accountPages() as $url) {
            $this->actingAs($this->agent, 'admin')->get($url)->assertOk()
                ->assertSee(route('admin.campaigns.page', $alpha), false)
                ->assertSee(route('admin.debts.page', $alpha), false);
        }
    }

    public function test_account_pages_keep_the_room_operated_last(): void
    {
        $this->room('alpha');
        $beta = $this->room('beta');

        $this->actingAs($this->agent, 'admin')->get(route('admin.dashboard.page', $beta))->assertOk();

        $this->actingAs($this->agent, 'admin')->get(route('admin.subscription.show'))->assertOk()
            ->assertSee(route('admin.campaigns.page', $beta), false);
    }

    public function test_a_foreign_room_in_the_session_is_never_used(): void
    {
        $own = $this->room('own');
        $other = Admin::create(['name' => 'Other', 'email' => 'other@drinkflow.test', 'password' => 'password123', 'status' => 'active']);
        $foreign = $this->room('foreign', $other);

        $this->actingAs($this->agent, 'admin')
            ->withSession([AdminLayoutComposer::CONTEXT_ROOM_SESSION_KEY => $foreign->id])
            ->get(route('admin.billing.index'))->assertOk()
            ->assertSee(route('admin.campaigns.page', $own), false)
            ->assertDontSee(route('admin.campaigns.page', $foreign), false);
    }

    public function test_agent_without_rooms_still_opens_the_account_pages(): void
    {
        foreach ($this->accountPages() as $url) {
            $this->actingAs($this->agent, 'admin')->get($url)->assertOk();
        }
    }
}
