<?php

namespace Tests\Feature;

use App\Actions\User\JoinRoomAction;
use App\Models\Feedback;
use App\Models\GlobalUser;
use App\Models\Room;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_accessing_me_feedback_redirects_to_home_or_referer(): void
    {
        $response = $this->get('/me/feedback');
        $response->assertRedirect('/');

        $refererResponse = $this->from(url('/versions'))->get('/me/feedback');
        $refererResponse->assertRedirect(url('/versions'));
    }

    public function test_authenticated_user_can_view_me_feedback_page_in_vietnamese(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
            'status' => 'active',
        ]);

        $room = Room::create(['name' => 'Ban Công nghệ & Kỹ thuật số', 'slug' => 'tech-team']);
        app(JoinRoomAction::class)->execute($user, $room, 'device-1', 'hash-1');

        $response = $this->withSession(['locale' => 'vi'])
            ->actingAs($user, 'web')
            ->get('/me/feedback');

        $response->assertOk();
        $response->assertSee('Đánh giá &amp; Góp ý phát triển DrinkFlow', false);
        $response->assertSee('Mức độ hài lòng của bạn');
        $response->assertSee('Phân hệ liên quan');
        $response->assertSee('Nội dung chi tiết');
        $response->assertSee('Thống kê mức độ hài lòng');
        $response->assertSee('Góp ý tiêu biểu gần đây');
    }

    public function test_authenticated_user_can_view_me_feedback_page_in_japanese(): void
    {
        $user = GlobalUser::create([
            'name' => 'Tanaka Taro',
            'normalized_name' => 'TANAKA TARO',
            'email' => 'tanaka@company.com',
            'status' => 'active',
        ]);

        $response = $this->withSession(['locale' => 'ja'])
            ->actingAs($user, 'web')
            ->get('/me/feedback');

        $response->assertOk();
        $response->assertSee('DrinkFlow 評価＆プラットフォーム改善のご提案', false);
        $response->assertSee('本日のフィードバック');
        $response->assertSee('満足度');
        $response->assertSee('関連機能');
        $response->assertSee('システム全体');
        $response->assertSee('まとめ注文＆ルーム');
        $response->assertSee('詳細内容');
        $response->assertSee('満足度統計');
        $response->assertSee('最近の注目フィードバック');
        $response->assertSee('メンバーからのフィードバックはまだありません');
        $response->assertSee('評価＆フィードバック');
    }

    public function test_authenticated_user_can_view_me_feedback_page_in_english(): void
    {
        $user = GlobalUser::create([
            'name' => 'John Doe',
            'normalized_name' => 'JOHN DOE',
            'email' => 'john.doe@company.com',
            'status' => 'active',
        ]);

        $response = $this->withSession(['locale' => 'en'])
            ->actingAs($user, 'web')
            ->get('/me/feedback');

        $response->assertOk();
        $response->assertSee('DrinkFlow Feedback &amp; Platform Suggestions', false);
        $response->assertSee('Your Feedback Today');
        $response->assertSee('Your Satisfaction Level');
        $response->assertSee('Related Subsystem');
        $response->assertSee('Detailed Feedback');
        $response->assertSee('Satisfaction Overview');
        $response->assertSee('Recent Featured Feedbacks');
        $response->assertSee('No feedback from members yet');
        $response->assertSee('Feedback &amp; Reviews', false);
    }

    public function test_user_can_submit_valid_feedback(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
            'status' => 'active',
        ]);

        $response = $this->withoutMiddleware(ValidateCsrfToken::class)
            ->actingAs($user, 'web')
            ->post('/me/feedback', [
                'rating' => 5,
                'subsystem' => 'split_qr',
                'content' => 'Tính năng quét VietQR đối soát thanh toán tự động rất nhanh và tiện lợi!',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('feedbacks', [
            'global_user_id' => $user->id,
            'rating' => 5,
            'subsystem' => 'split_qr',
            'content' => 'Tính năng quét VietQR đối soát thanh toán tự động rất nhanh và tiện lợi!',
        ]);
    }

    public function test_user_cannot_exceed_daily_quota_of_1_feedback(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
            'status' => 'active',
        ]);

        // First submission succeeds
        Feedback::create([
            'global_user_id' => $user->id,
            'rating' => 5,
            'subsystem' => 'all',
            'content' => 'Đánh giá lượt 1 trong ngày.',
            'created_at' => now(),
        ]);

        // Second submission on same day must be rejected
        $response = $this->withoutMiddleware(ValidateCsrfToken::class)
            ->actingAs($user, 'web')
            ->post('/me/feedback', [
                'rating' => 4,
                'subsystem' => 'room',
                'content' => 'Đánh giá lượt 2 trong ngày.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['quota']);

        $this->assertEquals(1, Feedback::where('global_user_id', $user->id)->count());
    }

    public function test_feedback_validation_requires_content_and_valid_rating(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
            'status' => 'active',
        ]);

        $response = $this->withoutMiddleware(ValidateCsrfToken::class)
            ->actingAs($user, 'web')
            ->post('/me/feedback', [
                'rating' => 10, // invalid rating (> 5)
                'subsystem' => 'invalid_subsystem',
                'content' => 'a', // too short (< 3)
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['rating', 'subsystem', 'content']);
    }
}
