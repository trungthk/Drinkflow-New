<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\User\JoinRoomAction;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\GlobalUser;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignBudgetFilterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Open the campaigns page as a room member for a campaign with the given per-item budget cap.
     */
    private function campaignsPage(int $maxBudget): \Illuminate\Testing\TestResponse
    {
        $user = GlobalUser::create([
            'name' => 'Budget Member',
            'normalized_name' => 'BUDGET MEMBER',
            'email' => "budget-{$maxBudget}@company.com",
            'status' => 'active',
        ]);
        $room = Room::create(['name' => 'Budget Room', 'slug' => "budget-room-{$maxBudget}", 'status' => 'active']);
        app(JoinRoomAction::class)->execute($user, $room, 'Chrome', "budget-hash-{$maxBudget}");
        Campaign::create([
            'room_id' => $room->id,
            'name' => 'Budget Campaign',
            'restaurant' => 'DrinkFlow Cafe',
            'status' => CampaignStatus::Active,
            'max_budget' => $maxBudget,
        ]);

        return $this->actingAs($user, 'web')->get("/rooms/{$room->slug}/campaigns");
    }

    /** The within-budget checkbox is offered (unchecked by default) when the campaign caps the item price. */
    public function test_budget_filter_checkbox_is_unchecked_by_default_and_shows_the_cap(): void
    {
        $this->campaignsPage(70000)
            ->assertOk()
            ->assertSee('x-model="withinBudgetOnly"', false)
            ->assertSee('withinBudgetOnly: false', false)
            ->assertSee(__('room.campaign.budget_filter_label', ['amount' => '70.000đ']));
    }

    /** Without a budget cap there is nothing to filter on, so the checkbox is not rendered. */
    public function test_budget_filter_checkbox_is_hidden_without_budget_cap(): void
    {
        $this->campaignsPage(0)
            ->assertOk()
            ->assertDontSee('x-model="withinBudgetOnly"', false);
    }
}
