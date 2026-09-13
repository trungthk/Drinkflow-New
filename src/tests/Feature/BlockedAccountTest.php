<?php

namespace Tests\Feature;

use App\Actions\User\JoinRoomAction;
use App\Enums\CampaignStatus;
use App\Enums\DebtStatus;
use App\Models\Campaign;
use App\Models\Debt;
use App\Models\GlobalUser;
use App\Models\Room;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockedAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_accessing_blocked_redirects_to_home_or_referer(): void
    {
        $response = $this->get('/blocked');
        $response->assertRedirect('/');

        $refererResponse = $this->from(url('/terms'))->get('/blocked');
        $refererResponse->assertRedirect(url('/terms'));
    }

    public function test_blocked_user_is_redirected_to_blocked_page_from_portal(): void
    {
        $user = GlobalUser::create([
            'name' => 'Lê Tuấn Trung',
            'normalized_name' => 'LE TUAN TRUNG',
            'email' => 'trung.lt@company.com',
            'status' => 'blocked',
        ]);

        $response = $this->actingAs($user, 'web')->get('/me');
        $response->assertRedirect(route('user.blocked'));
    }

    public function test_blocked_user_can_view_blocked_page_with_incident_info(): void
    {
        $user = GlobalUser::create([
            'name' => 'Lê Tuấn Trung',
            'normalized_name' => 'LE TUAN TRUNG',
            'email' => 'trung.lt@company.com',
            'status' => 'active',
        ]);

        $room = Room::create(['name' => 'Tech Team', 'slug' => 'tech-team']);
        $roomUser = app(JoinRoomAction::class)->execute($user, $room, 'device-1', 'hash-1');
        $user->update(['status' => 'blocked']);

        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Trưa Thứ 6',
            'restaurant' => 'Phê La',
            'status' => CampaignStatus::Closed,
        ]);

        Debt::create([
            'room_id' => $room->id,
            'campaign_id' => $campaign->id,
            'room_user_id' => $roomUser->id,
            'original_amount' => 60000,
            'remaining_amount' => 60000,
            'status' => \App\Enums\DebtStatus::Unpaid,
        ]);

        $response = $this->actingAs($user, 'web')->get('/blocked');

        $response->assertOk();
        $response->assertSee('Tài khoản của bạn tạm thời bị khóa truy cập');
        $response->assertSee('trung.lt@company.com');
        $response->assertSee('Thông tin hồ sơ sự cố');
        $response->assertSee('Khoản chờ đối soát');
        $response->assertSee('60,000đ');
        $response->assertSee('Gửi yêu cầu mở khóa &amp; Khiếu nại', false);
    }

    public function test_blocked_user_can_submit_appeal(): void
    {
        $user = GlobalUser::create([
            'name' => 'Lê Tuấn Trung',
            'normalized_name' => 'LE TUAN TRUNG',
            'email' => 'trung.lt@company.com',
            'status' => 'blocked',
        ]);

        $response = $this->withoutMiddleware(ValidateCsrfToken::class)
            ->actingAs($user, 'web')
            ->post('/blocked/appeal', [
                'reason' => 'Đã chuyển tiền cho Host chiều nay qua VietQR.',
                'attachment_note' => 'GD123456789 - 0912345678',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
    }
}
