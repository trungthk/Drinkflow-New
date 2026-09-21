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

class DefaultOrderNoteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a member with an optional default note, a room and an active campaign.
     *
     * @return array{0: GlobalUser, 1: Room, 2: Campaign}
     */
    private function makeMember(?string $defaultNote): array
    {
        $user = GlobalUser::create([
            'name' => 'Note Member',
            'normalized_name' => 'NOTE MEMBER',
            'email' => 'note-member@company.com',
            'status' => 'active',
            'preferences' => $defaultNote === null ? null : ['note' => $defaultNote],
        ]);
        $room = Room::create(['name' => 'Note Room', 'slug' => 'note-room', 'status' => 'active']);
        app(JoinRoomAction::class)->execute($user, $room, 'Chrome', 'note-hash');
        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Note Campaign',
            'restaurant' => 'DrinkFlow Cafe',
            'status' => CampaignStatus::Active,
        ]);

        return [$user, $room, $campaign];
    }

    /** The accessor trims the stored preference and falls back to an empty string. */
    public function test_default_order_note_accessor_handles_missing_and_padded_values(): void
    {
        $this->assertSame('', (new GlobalUser())->default_order_note);
        $this->assertSame('', (new GlobalUser(['preferences' => ['note' => null]]))->default_order_note);
        $this->assertSame('Less sugar', (new GlobalUser(['preferences' => ['note' => "  Less sugar \n"]]))->default_order_note);
    }

    /** The item customization modal is pre-filled from the member's default note. */
    public function test_campaign_page_exposes_default_note_for_customize_modal(): void
    {
        [$user, $room] = $this->makeMember('Less sugar, no ice');

        $this->actingAs($user, 'web')->get("/rooms/{$room->slug}/campaigns")
            ->assertOk()
            ->assertSee("defaultNote: 'Less sugar, no ice'", false)
            ->assertSee('this.note = this.defaultNote;', false);
    }

    /** Without a saved default the modal note starts empty. */
    public function test_campaign_page_default_note_is_empty_when_not_configured(): void
    {
        [$user, $room] = $this->makeMember(null);

        $this->actingAs($user, 'web')->get("/rooms/{$room->slug}/campaigns")
            ->assertOk()
            ->assertSee("defaultNote: ''", false);
    }

    /** The standalone order page pre-fills its note textarea as well. */
    public function test_order_page_prefills_note_textarea(): void
    {
        [$user, $room, $campaign] = $this->makeMember('Separate the topping');

        $this->actingAs($user, 'web')->get("/rooms/{$room->slug}/campaigns/{$campaign->id}/order")
            ->assertOk()
            ->assertSee('>Separate the topping</textarea>', false);
    }
}
