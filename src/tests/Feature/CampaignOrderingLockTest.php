<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\CampaignItem;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\Room;
use App\Models\RoomUser;
use App\Models\UserNotification;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CampaignOrderingLockTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Room $room;

    private Campaign $campaign;

    private CampaignItem $item;

    private GlobalUser $user;

    private RoomUser $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->admin = Admin::create([
            'name' => 'Room Admin',
            'email' => 'lock-admin@example.test',
            'password' => Hash::make('secret'),
            'status' => 'active',
        ]);
        $this->room = Room::create(['name' => 'Lock Room', 'slug' => 'lock-room', 'status' => 'active']);
        $this->admin->rooms()->attach($this->room);

        $this->campaign = Campaign::create([
            'room_id' => $this->room->id,
            'name' => 'Trà chiều',
            'restaurant' => 'Cafe',
            'status' => CampaignStatus::Active,
            'deadline' => now()->addHour(),
        ]);
        $this->item = $this->campaign->items()->create([
            'name' => 'Trà sữa',
            'normalized_name' => 'tra sua',
            'base_price' => 30000,
            'status' => 'active',
        ]);

        $this->user = GlobalUser::create(['name' => 'Member', 'email' => 'lock-member@example.test', 'status' => 'active']);
        $this->member = RoomUser::create(['room_id' => $this->room->id, 'global_user_id' => $this->user->id, 'display_name' => 'Member', 'status' => 'active']);
    }

    private function lock(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.campaigns.lock-ordering', [$this->room, $this->campaign]))
            ->assertOk();
    }

    public function test_admin_can_lock_and_unlock_ordering(): void
    {
        $this->lock();
        $this->assertTrue($this->campaign->fresh()->isOrderingLocked());
        $this->assertSame(CampaignStatus::Active, $this->campaign->fresh()->status);
        $this->assertTrue(AuditLog::query()->where('event', 'campaign.ordering_locked')->exists());

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.campaigns.unlock-ordering', [$this->room, $this->campaign]))
            ->assertOk();
        $this->assertFalse($this->campaign->fresh()->isOrderingLocked());
        $this->assertTrue(AuditLog::query()->where('event', 'campaign.ordering_unlocked')->exists());
    }

    public function test_member_cannot_order_or_use_cart_while_locked(): void
    {
        $this->lock();

        $this->actingAs($this->user, 'web')
            ->postJson(route('user.orders.store', [$this->room, $this->campaign]), ['items' => [['item_id' => $this->item->id, 'quantity' => 1]]])
            ->assertUnprocessable()
            ->assertJsonFragment([__('room.campaign.ordering_locked')]);

        $this->actingAs($this->user, 'web')
            ->postJson(route('user.campaigns.cart.store', [$this->room, $this->campaign]), ['item_id' => $this->item->id, 'quantity' => 1])
            ->assertUnprocessable();

        $this->assertSame(0, Order::query()->count());
    }

    public function test_member_can_order_again_after_unlock(): void
    {
        $this->lock();
        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.campaigns.unlock-ordering', [$this->room, $this->campaign]))
            ->assertOk();

        $this->actingAs($this->user, 'web')
            ->postJson(route('user.orders.store', [$this->room, $this->campaign]), ['items' => [['item_id' => $this->item->id, 'quantity' => 1]]])
            ->assertSuccessful();

        $this->assertSame(1, Order::query()->count());
    }

    public function test_admin_can_still_order_on_behalf_while_locked(): void
    {
        $this->lock();

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.orders.on-behalf', $this->room), [
                'campaign_id' => $this->campaign->id,
                'room_user_id' => $this->member->id,
                'items' => [['item_id' => $this->item->id, 'quantity' => 1]],
            ])
            ->assertCreated();
    }

    public function test_locked_campaign_shows_banner_and_redirects_order_page(): void
    {
        $this->lock();

        $this->actingAs($this->user, 'web')
            ->get(route('user.campaigns.index', $this->room->slug))
            ->assertOk()
            ->assertSee(__('room.campaign.ordering_locked_title'));

        $this->actingAs($this->user, 'web')
            ->get(route('user.campaigns.order-page', [$this->room, $this->campaign]))
            ->assertRedirect(route('user.campaigns.index', $this->room->slug));
    }

    public function test_only_active_campaign_can_be_locked(): void
    {
        $this->campaign->update(['status' => CampaignStatus::Closed]);

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.campaigns.lock-ordering', [$this->room, $this->campaign]))
            ->assertUnprocessable();
        $this->assertFalse($this->campaign->fresh()->isOrderingLocked());
    }

    public function test_lock_notifies_members_only_when_requested(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.campaigns.lock-ordering', [$this->room, $this->campaign]), ['notify' => false])
            ->assertOk();
        $this->assertSame(0, UserNotification::query()->count());

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.campaigns.unlock-ordering', [$this->room, $this->campaign]), ['notify' => true])
            ->assertOk();

        $notification = UserNotification::query()->where('global_user_id', $this->user->id)->sole();
        $this->assertSame(__('messages.campaign_ordering_unlocked_title'), $notification->title);
        $this->assertSame(route('user.campaigns.index', $this->room->slug), $notification->link);
        $this->assertFalse($notification->data['ordering_locked']);
    }

    public function test_expired_campaign_cannot_be_locked_or_unlocked(): void
    {
        $this->campaign->update(['deadline' => now()->subMinute()]);

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.campaigns.lock-ordering', [$this->room, $this->campaign]))
            ->assertUnprocessable()
            ->assertJsonFragment([__('admin.campaign_lock_not_active')]);
        $this->assertFalse($this->campaign->fresh()->isOrderingLocked());

        $this->campaign->forceFill(['ordering_locked_at' => now()])->save();
        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.campaigns.unlock-ordering', [$this->room, $this->campaign]))
            ->assertUnprocessable();
        $this->assertTrue($this->campaign->fresh()->isOrderingLocked());
    }

    public function test_campaign_page_shows_toggle_and_confirm_modal_only_before_deadline(): void
    {
        $url = route('admin.campaigns.info', [$this->room, $this->campaign]);
        $this->actingAs($this->admin, 'admin')->get($url)
            ->assertOk()
            ->assertSee('data-mode="lock"', false)
            ->assertSee('id="campaign-ordering-lock-modal"', false)
            ->assertSee(__('admin.campaign_lock_notify_label'));

        $this->campaign->update(['deadline' => now()->subMinute()]);
        $this->actingAs($this->admin, 'admin')->get($url)
            ->assertOk()
            ->assertDontSee('data-ordering-lock-toggle', false)
            ->assertDontSee('id="campaign-ordering-lock-modal"', false);
    }

    public function test_admin_of_another_room_cannot_lock(): void
    {
        $stranger = Admin::create([
            'name' => 'Stranger',
            'email' => 'lock-stranger@example.test',
            'password' => Hash::make('secret'),
            'status' => 'active',
        ]);

        $response = $this->actingAs($stranger, 'admin')
            ->postJson(route('admin.campaigns.lock-ordering', [$this->room, $this->campaign]));

        $this->assertContains($response->status(), [403, 404]);
        $this->assertFalse($this->campaign->fresh()->isOrderingLocked());
    }
}
