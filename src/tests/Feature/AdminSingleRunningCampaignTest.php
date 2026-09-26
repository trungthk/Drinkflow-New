<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\CampaignStatus;
use App\Enums\OrderStatus;
use App\Models\AdminAccount;
use App\Models\Campaign;
use App\Models\CampaignItem;
use App\Models\GlobalUser;
use App\Models\Order;
use App\Models\PaymentAccount;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSingleRunningCampaignTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A room with a running campaign cannot get a new campaign through create, duplicate or activate.
     *
     * @return void
     */
    public function test_new_campaign_is_blocked_while_another_is_running(): void
    {
        [$admin, $room] = $this->adminWithRoom();
        $running = Campaign::create(['room_id' => $room->id, 'name' => 'Live', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Active]);
        $account = PaymentAccount::create(['room_id' => $room->id, 'bank_code' => 'VCB', 'bank_name' => 'Vietcombank', 'account_number' => '123456', 'account_name' => 'DrinkFlow', 'status' => 'active']);
        $draft = Campaign::create(['room_id' => $room->id, 'name' => 'Draft', 'restaurant' => 'Cafe', 'payment_account_id' => $account->id, 'status' => CampaignStatus::Draft]);
        CampaignItem::create(['campaign_id' => $draft->id, 'name' => 'Oolong', 'normalized_name' => 'OOLONG', 'base_price' => 30000, 'status' => 'active']);

        $this->actingAs($admin, 'admin');

        $this->get(route('admin.campaigns.create', $room))
            ->assertRedirect(route('admin.campaigns.page', $room))
            ->assertSessionHas('error', __('admin.campaign_running_exists'));

        $this->postJson("/admin/{$room->id}/campaigns", ['restaurant' => 'Cafe', 'sponsor_type' => 'none', 'max_budget' => 70000])
            ->assertUnprocessable()
            ->assertJsonPath('errors.campaign.0', __('admin.campaign_running_exists'));

        $this->postJson("/admin/{$room->id}/campaigns/{$running->id}/duplicate")->assertUnprocessable();
        $this->postJson("/admin/{$room->id}/campaigns/{$draft->id}/activate")->assertUnprocessable();

        $this->assertSame(2, Campaign::where('room_id', $room->id)->count());
        $this->assertSame(CampaignStatus::Draft, $draft->fresh()->status);

        // Once the running campaign is closed, creating a new one works again.
        $running->update(['status' => CampaignStatus::Closed]);
        $this->postJson("/admin/{$room->id}/campaigns", ['restaurant' => 'Cafe', 'sponsor_type' => 'none', 'max_budget' => 70000])
            ->assertCreated();
    }

    /**
     * The order management page only lists orders of the latest campaign.
     *
     * @return void
     */
    public function test_order_management_lists_only_latest_campaign_orders(): void
    {
        [$admin, $room] = $this->adminWithRoom();
        $older = Campaign::create(['room_id' => $room->id, 'name' => 'Older', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Closed]);
        $latest = Campaign::create(['room_id' => $room->id, 'name' => 'Latest', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Closed]);
        Campaign::create(['room_id' => $room->id, 'name' => 'Archived newest', 'restaurant' => 'Cafe', 'status' => CampaignStatus::Archived]);

        $member = $this->roomUser($room);
        $oldOrder = Order::create(['room_id' => $room->id, 'campaign_id' => $older->id, 'room_user_id' => $member->id, 'subtotal' => 1000, 'final_amount' => 1000, 'status' => OrderStatus::Completed, 'note' => 'OLD-CAMPAIGN-NOTE']);
        $newOrder = Order::create(['room_id' => $room->id, 'campaign_id' => $latest->id, 'room_user_id' => $member->id, 'subtotal' => 1000, 'final_amount' => 1000, 'status' => OrderStatus::Completed, 'note' => 'LATEST-CAMPAIGN-NOTE']);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.orders.page', $room))->assertOk();

        $orders = $response->viewData('orders');
        $this->assertSame([$newOrder->id], $orders->pluck('id')->all());
        $this->assertNotContains($oldOrder->id, $orders->pluck('id')->all());
        $this->assertSame($latest->id, $response->viewData('activeCampaign')->id);
    }

    /**
     * Create an admin assigned to a fresh room.
     *
     * @return array{0: AdminAccount, 1: Room}
     */
    private function adminWithRoom(): array
    {
        $admin = AdminAccount::create(['name' => 'Room Admin', 'email' => 'single-running@example.test', 'password' => Hash::make('secret'), 'role' => AdminRole::Admin, 'status' => 'active']);
        $room = Room::create(['name' => 'Single Running', 'slug' => 'single-running', 'status' => 'active']);
        $admin->rooms()->attach($room);

        return [$admin, $room];
    }

    /**
     * Create an active member in the room.
     *
     * @param Room $room Room that owns the member.
     * @return RoomUser Created member.
     */
    private function roomUser(Room $room): RoomUser
    {
        $user = GlobalUser::create(['name' => 'Member', 'normalized_name' => 'MEMBER', 'email' => 'member@example.test', 'status' => 'active']);

        return RoomUser::create([
            'room_id' => $room->id,
            'global_user_id' => $user->id,
            'user_code' => 'USR-'.$user->id,
            'display_name' => 'Member',
            'normalized_name' => 'MEMBER',
            'status' => 'active',
        ]);
    }
}
