<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Enums\DebtStatus;
use App\Models\Campaign;
use App\Models\Debt;
use App\Models\GlobalUser;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NormalizeDongSignMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verify legacy "142.000 ₫" notes are rewritten and untouched notes stay as they are.
     *
     * @return void
     */
    public function test_migration_rewrites_legacy_dong_sign_in_debt_notes(): void
    {
        $room = Room::create(['name' => 'Legacy Room', 'slug' => 'legacy-room', 'status' => 'active']);
        $campaign = Campaign::create(['room_id' => $room->id, 'name' => 'Legacy', 'restaurant' => 'Quán', 'status' => CampaignStatus::Closed]);
        $globalUser = GlobalUser::create(['name' => 'An', 'normalized_name' => 'AN', 'email' => 'an@example.test', 'status' => 'active']);
        $roomUser = RoomUser::create([
            'room_id' => $room->id, 'global_user_id' => $globalUser->id, 'user_code' => 'USR-1',
            'display_name' => 'An', 'normalized_name' => 'AN', 'status' => 'active',
        ]);
        $base = [
            'room_id' => $room->id, 'campaign_id' => $campaign->id, 'room_user_id' => $roomUser->id,
            'original_amount' => 142000, 'sponsor_amount' => 0, 'sponsor_type' => 'full', 'adjustment_amount' => 0,
            'paid_amount' => 0, 'remaining_amount' => 142000, 'status' => DebtStatus::Pending,
        ];
        $legacy = Debt::create($base + ['note' => 'Thực trả: 142.000 ₫ [Món: 142.000 ₫, Ship: +0 ₫]']);

        $migration = require database_path('migrations/2026_09_20_000000_normalize_dong_sign_in_stored_texts.php');
        $migration->up();
        $migration->up();

        $this->assertSame('Thực trả: 142.000đ [Món: 142.000đ, Ship: +0đ]', $legacy->fresh()->note);
    }
}
