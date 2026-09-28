<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Campaign;
use App\Models\Debt;
use App\Models\Order;
use App\Services\Code\CodeGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodeGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test order code format and uniqueness generation.
     */
    public function test_generate_order_code_format(): void
    {
        $service = new CodeGeneratorService();
        $code = $service->generateOrderCode();

        $this->assertNotEmpty($code);
        $this->assertMatchesRegularExpression('/^ORD-\d{8}-[A-Z0-9]{4}$/', $code);
    }

    /**
     * Test campaign code format and uniqueness generation.
     */
    public function test_generate_campaign_code_format(): void
    {
        $service = new CodeGeneratorService();
        $code = $service->generateCampaignCode();

        $this->assertNotEmpty($code);
        $this->assertMatchesRegularExpression('/^CMP-\d{8}-[A-Z0-9]{4}$/', $code);
    }

    /**
     * Test debt code format and uniqueness generation.
     */
    public function test_generate_debt_code_format(): void
    {
        $code = CodeGeneratorService::generateDebtCode(1, \Illuminate\Support\Carbon::parse('2026-09-28'));

        $this->assertMatchesRegularExpression('/^2609281[A-Z0-9]{4}$/', $code);
        $this->assertMatchesRegularExpression('/^' . now()->format('ymd') . '42[A-Z0-9]{4}$/', CodeGeneratorService::generateDebtCode(42));
    }

    /**
     * Test global user code format and uniqueness generation.
     */
    public function test_generate_global_user_code_format(): void
    {
        $code = CodeGeneratorService::generateGlobalUserCode();

        $this->assertNotEmpty($code);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $code);
    }

    /**
     * New debts get a code with the room id; a code set explicitly (e.g. a legacy code) is kept as is.
     */
    public function test_debt_model_uses_room_debt_code_and_keeps_explicit_codes(): void
    {
        $room = \App\Models\Room::create(['name' => 'Code Room', 'slug' => 'code-room', 'status' => 'active']);
        $campaign = Campaign::create(['room_id' => $room->id, 'name' => 'Trà', 'restaurant' => 'Cafe', 'status' => 'active']);
        $user = \App\Models\GlobalUser::create(['name' => 'Member', 'email' => 'code-member@example.test', 'status' => 'active']);
        $roomUser = \App\Models\RoomUser::create(['room_id' => $room->id, 'global_user_id' => $user->id, 'display_name' => 'Member', 'status' => 'active']);
        $attributes = ['room_id' => $room->id, 'campaign_id' => $campaign->id, 'room_user_id' => $roomUser->id, 'original_amount' => 30000, 'remaining_amount' => 30000, 'status' => 'unpaid'];

        $debt = Debt::create($attributes);
        $otherUser = \App\Models\GlobalUser::create(['name' => 'Other', 'email' => 'code-other@example.test', 'status' => 'active']);
        $otherRoomUser = \App\Models\RoomUser::create(['room_id' => $room->id, 'global_user_id' => $otherUser->id, 'display_name' => 'Other', 'status' => 'active']);
        $legacy = Debt::create(['room_user_id' => $otherRoomUser->id, 'code' => 'DEB-20260926-OMPY'] + $attributes);

        $this->assertMatchesRegularExpression('/^' . now()->format('ymd') . $room->id . '[A-Z0-9]{4}$/', $debt->code);
        $this->assertSame('DEB-20260926-OMPY', $legacy->code);
    }

    /**
     * Test room user code format and uniqueness generation.
     */
    public function test_generate_room_user_code_format(): void
    {
        $code = CodeGeneratorService::generateRoomUserCode();

        $this->assertNotEmpty($code);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $code);
    }
}
