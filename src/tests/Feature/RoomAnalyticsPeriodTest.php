<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Enums\GlobalUserStatus;
use App\Enums\OrderStatus;
use App\Enums\RoomStatus;
use App\Enums\RoomUserStatus;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomAnalyticsPeriodTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Filter all metrics and item totals by calendar boundaries and membership.
     *
     * @return void
     */
    public function test_period_filters_metrics_and_items_without_leaking_other_members(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 16)->setTime(12, 0));
        $room = Room::create(['name' => 'Marketing', 'slug' => 'analytics-marketing', 'status' => RoomStatus::Active]);
        $user = GlobalUser::create(['name' => 'Member', 'email' => 'analytics-period@example.test', 'status' => GlobalUserStatus::Active]);
        $member = $this->member($room, $user);
        foreach (['2025-12-31 23:59:59', '2026-01-01 00:00:00', '2026-07-01 00:00:00', '2026-09-14 00:00:00', '2026-10-01 00:00:00'] as $date) {
            $this->order($member, $date);
        }
        $this->order($member, '2026-09-03 12:00:00', OrderStatus::Submitted, CampaignStatus::Active);
        $this->order($member, '2026-09-02 12:00:00', OrderStatus::Cancelled);
        $otherUser = GlobalUser::create(['name' => 'Other', 'email' => 'analytics-other@example.test', 'status' => GlobalUserStatus::Active]);
        $this->order($this->member($room, $otherUser), '2026-09-01 00:00:00');
        $otherRoom = Room::create(['name' => 'Other room', 'slug' => 'analytics-other', 'status' => RoomStatus::Active]);
        $this->order($this->member($otherRoom, $user), '2026-09-01 00:00:00');

        $this->actingAs($user, 'web');
        foreach (['week' => 1, 'month' => 1, 'quarter' => 2, 'year' => 4] as $period => $count) {
            $this->getJson(route('user.analytics.room', ['room' => $room->slug, 'period' => $period]))
                ->assertOk()
                ->assertJsonPath('data.period', $period)
                ->assertJsonPath('data.total_orders', $count)
                ->assertJsonPath('data.total_cups', $count * 2)
                ->assertJsonPath('data.total_amount', $count * 20000)
                ->assertJsonPath('data.sponsor_received', $count * 5000)
                ->assertJsonPath('data.top_items.0.quantity', $count * 2)
                ->assertJsonPath('data.top_items.0.total_amount', $count * 25000);
        }
        $this->getJson(route('user.analytics.room', $room->slug))
            ->assertJsonPath('data.period', 'week')
            ->assertJsonPath('data.period_start', '2026-09-14')
            ->assertJsonPath('data.period_end', '2026-09-20');
        $this->getJson(route('user.analytics.room', ['room' => $room->slug, 'period' => 'week']))
            ->assertJsonPath('data.period_start', '2026-09-14')
            ->assertJsonPath('data.period_end', '2026-09-20');
        $this->get(route('user.analytics.room', ['room' => $room->slug, 'period' => 'week']))
            ->assertOk()
            ->assertSee(__('room.analytics.filter_week'))
            ->assertSee('bg-[#006948] text-white', false);
        $this->getJson(route('user.analytics.room', ['room' => $room->slug, 'period' => 'invalid']))
            ->assertUnprocessable()->assertJsonValidationErrors('period');
    }

    /**
     * Empty analytics display real zeros and the localized empty state.
     *
     * @return void
     */
    public function test_empty_period_has_zero_metrics_and_empty_table(): void
    {
        $room = Room::create(['name' => 'Empty', 'slug' => 'analytics-empty', 'status' => RoomStatus::Active]);
        $user = GlobalUser::create(['name' => 'Member', 'email' => 'analytics-empty@example.test', 'status' => GlobalUserStatus::Active]);
        $this->member($room, $user);
        $this->actingAs($user, 'web')->get(route('user.analytics.room', $room->slug))
            ->assertOk()->assertViewHas('participationRate', 0)->assertViewHas('sponsorReceived', 0)
            ->assertSee(__('room.analytics.no_favorite_item'))
            ->assertSee(__('room.analytics.no_data_period'));
    }

    /**
     * The whole-year filter and the "room · for user" subtitle are hidden from the page header.
     *
     * @return void
     */
    public function test_page_hides_year_filter_and_room_user_subtitle(): void
    {
        $room = Room::create(['name' => 'Hidden Header Room', 'slug' => 'analytics-header', 'status' => RoomStatus::Active]);
        $user = GlobalUser::create(['name' => 'Header Member', 'email' => 'analytics-header@example.test', 'status' => GlobalUserStatus::Active]);
        $this->member($room, $user);

        $response = $this->actingAs($user, 'web')->get(route('user.analytics.room', $room->slug))->assertOk();

        $response->assertSee(__('room.analytics.filter_week'))
            ->assertSee(__('room.analytics.filter_month'))
            ->assertSee(__('room.analytics.filter_quarter'))
            ->assertDontSee(__('room.analytics.filter_year'))
            ->assertDontSee('period=year', false)
            ->assertDontSee(__('room.analytics.for_user').' Header Member');
    }

    /**
     * Create a membership without trusted device cookies.
     *
     * @param Room $room Target room.
     * @param GlobalUser $user Member account.
     * @return RoomUser Active room membership.
     */
    private function member(Room $room, GlobalUser $user): RoomUser
    {
        return RoomUser::create([
            'room_id' => $room->id, 'global_user_id' => $user->id,
            'display_name' => $user->name,
            'normalized_name' => strtoupper($user->name), 'status' => RoomUserStatus::Active, 'joined_at' => now(),
        ]);
    }

    /**
     * Create a dated order and its item for range assertions.
     *
     * @param RoomUser $member Owner of the order.
     * @param string $date Timestamp used for the order and campaign.
     * @param OrderStatus $status Business status of the order.
     * @param CampaignStatus $campaignStatus Lifecycle status of the campaign.
     * @return void
     */
    private function order(
        RoomUser $member,
        string $date,
        OrderStatus $status = OrderStatus::Submitted,
        CampaignStatus $campaignStatus = CampaignStatus::Closed,
    ): void
    {
        $campaign = Campaign::create([
            'room_id' => $member->room_id, 'name' => 'Coffee', 'restaurant' => 'Test Store',
            'status' => $campaignStatus,
            'closed_at' => $campaignStatus === CampaignStatus::Closed ? $date : null,
            'created_at' => $date, 'updated_at' => $date,
        ]);
        $order = $member->orders()->create([
            'room_id' => $member->room_id, 'campaign_id' => $campaign->id,
            'subtotal' => 25000, 'final_amount' => 20000, 'sponsor_amount' => 5000,
            'status' => $status, 'created_at' => $date, 'updated_at' => $date,
        ]);
        $order->items()->create(['item_name' => 'Coffee', 'unit_price' => 12500, 'quantity' => 2, 'line_subtotal' => 25000]);
    }
}
