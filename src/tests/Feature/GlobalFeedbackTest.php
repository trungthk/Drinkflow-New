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
        \Illuminate\Support\Facades\Config::set('captcha.disable', true);

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

    public function test_feedback_submission_requires_captcha_by_default(): void
    {
        \Illuminate\Support\Facades\Config::set('captcha.disable', false);

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
                'content' => 'Feedback không kèm mã captcha.',
                // omitted captcha
            ]);

        $response->assertSessionHasErrors(['captcha']);
    }

    public function test_user_cannot_exceed_daily_quota_of_1_feedback(): void
    {
        \Illuminate\Support\Facades\Config::set('captcha.disable', true);

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
        \Illuminate\Support\Facades\Config::set('captcha.disable', true);

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

    public function test_feedback_load_more_returns_5_items_ordered_by_created_at_desc(): void
    {
        $user = GlobalUser::create([
            'name' => 'Trung Lê',
            'normalized_name' => 'TRUNG LE',
            'email' => 'trung.lt@company.com',
            'status' => 'active',
        ]);

        // 12 approved feedbacks (rating 3-5) with distinct timestamps...
        for ($i = 1; $i <= 12; $i++) {
            Feedback::forceCreate([
                'global_user_id' => $user->id,
                'rating' => ($i % 3) + 3,
                'subsystem' => 'all',
                'content' => "Góp ý số {$i}",
                'status' => 'active',
                'user_display_name' => "User {$i}",
                'created_at' => now()->subMinutes(100 - $i),
                'updated_at' => now()->subMinutes(100 - $i),
            ]);
        }
        // ...plus feedback that must never be listed: pending, or approved with a low rating.
        Feedback::forceCreate(['global_user_id' => $user->id, 'rating' => 5, 'subsystem' => 'all', 'content' => 'Chưa duyệt', 'status' => 'inactive', 'created_at' => now(), 'updated_at' => now()]);
        Feedback::forceCreate(['global_user_id' => $user->id, 'rating' => 2, 'subsystem' => 'all', 'content' => 'Điểm thấp', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);

        // Page 1: 5 items
        $res1 = $this->actingAs($user, 'web')
            ->getJson('/me/feedback?page=1');

        $res1->assertOk()
            ->assertJson([
                'success' => true,
                'current_page' => 1,
                'has_more' => true,
                'next_page' => 2,
                'total' => 12,
                'count' => 5,
            ]);

        $data1 = $res1->json('data');
        $this->assertCount(5, $data1);
        $this->assertSame('Góp ý số 12', $data1[0]['content']); // Latest first
        $this->assertSame('Góp ý số 11', $data1[1]['content']);

        // Page 2: next 5 items
        $res2 = $this->actingAs($user, 'web')
            ->getJson('/me/feedback?page=2');

        $res2->assertOk()
            ->assertJson([
                'success' => true,
                'current_page' => 2,
                'has_more' => true,
                'next_page' => 3,
                'count' => 5,
            ]);

        // Page 3: final 2 items
        $res3 = $this->actingAs($user, 'web')
            ->getJson('/me/feedback?page=3');

        $res3->assertOk()
            ->assertJson([
                'success' => true,
                'current_page' => 3,
                'has_more' => false,
                'next_page' => null,
                'count' => 2,
            ]);
    }
}
