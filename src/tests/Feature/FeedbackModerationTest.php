<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\FeedbackStatus;
use App\Models\AdminAccount;
use App\Models\Feedback;
use App\Models\GlobalUser;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class FeedbackModerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    /** New feedback is stored as inactive and is not shown until approved. */
    public function test_submitted_feedback_is_inactive_by_default(): void
    {
        Config::set('captcha.disable', true);
        $user = $this->user('submitter@company.com');

        $this->actingAs($user, 'web')->post('/me/feedback', [
            'rating' => 5,
            'subsystem' => 'all',
            'content' => 'Rất tuyệt vời, giao diện dễ dùng và nhanh.',
        ])->assertRedirect();

        $feedback = Feedback::query()->firstOrFail();
        $this->assertSame(FeedbackStatus::Inactive, $feedback->status);
        $this->assertDatabaseHas('feedbacks', ['id' => $feedback->id, 'status' => 'inactive']);

        $this->actingAs($user, 'web')->get('/me/feedback')->assertOk()->assertDontSee('Rất tuyệt vời, giao diện dễ dùng và nhanh.');
    }

    /** A row created without an explicit status falls back to the database default (inactive). */
    public function test_database_default_status_is_inactive(): void
    {
        $id = Feedback::forceCreate(['rating' => 4, 'subsystem' => 'all', 'content' => 'Mặc định'])->id;

        $this->assertSame(FeedbackStatus::Inactive, Feedback::query()->findOrFail($id)->status);
    }

    /** The feedback page (list and statistics) only includes active feedback rated 3 stars or more. */
    public function test_feedback_page_only_shows_active_feedback_with_rating_three_or_more(): void
    {
        $user = $this->user('reader@company.com');
        $this->feedback('Hiển thị 5 sao', 5, 'active');
        $this->feedback('Hiển thị 3 sao', 3, 'active');
        $this->feedback('Ẩn vì dưới 3 sao', 2, 'active');
        $this->feedback('Ẩn vì chưa duyệt', 5, 'inactive');

        $response = $this->actingAs($user, 'web')->get('/me/feedback')->assertOk();
        $response->assertSee('Hiển thị 5 sao')->assertSee('Hiển thị 3 sao');
        $response->assertDontSee('Ẩn vì dưới 3 sao')->assertDontSee('Ẩn vì chưa duyệt');

        $this->assertSame(2, $response->viewData('totalCount'));
        $this->assertEquals(4.0, $response->viewData('avgScore'));

        $this->actingAs($user, 'web')->getJson('/me/feedback')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonCount(2, 'data');
    }

    /** A superadmin approves a feedback and it then appears on the page; deactivating hides it again. */
    public function test_superadmin_approval_controls_visibility(): void
    {
        $root = $this->superadmin();
        $user = $this->user('viewer@company.com');
        $feedback = $this->feedback('Chờ được duyệt', 4, 'inactive');

        $this->actingAs($root, 'admin')->patchJson(route('superadmin.feedbacks.status', $feedback), ['status' => 'active'])
            ->assertOk()->assertJsonPath('data.status', 'active');
        $this->assertSame(FeedbackStatus::Active, $feedback->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'feedback.status_changed', 'target_id' => $feedback->id]);
        $this->actingAs($user, 'web')->get('/me/feedback')->assertSee('Chờ được duyệt');

        $this->actingAs($root, 'admin')->patchJson(route('superadmin.feedbacks.status', $feedback), ['status' => 'inactive'])->assertOk();
        $this->actingAs($user, 'web')->get('/me/feedback')->assertDontSee('Chờ được duyệt');
    }

    /** Only superadmins can moderate. */
    public function test_moderation_is_restricted_and_validated(): void
    {
        $feedback = $this->feedback('Nội dung', 5, 'inactive');
        $roomAdmin = AdminAccount::create(['name' => 'Room Admin', 'email' => 'roomadmin@drinkflow.test', 'password' => 'password123', 'role' => AdminRole::Admin, 'status' => 'active']);

        // Guest first: actingAs() keeps the user signed in for the rest of the test.
        $this->patchJson(route('superadmin.feedbacks.status', $feedback), ['status' => 'active'])->assertUnauthorized();
        $this->actingAs($roomAdmin, 'admin')->patchJson(route('superadmin.feedbacks.status', $feedback), ['status' => 'active'])->assertForbidden();

        $this->assertSame(FeedbackStatus::Inactive, $feedback->fresh()->status);
    }

    /** Unknown statuses are rejected. */
    public function test_moderation_rejects_unknown_status(): void
    {
        $feedback = $this->feedback('Nội dung', 5, 'inactive');

        $this->actingAs($this->superadmin(), 'admin')->patchJson(route('superadmin.feedbacks.status', $feedback), ['status' => 'published'])
            ->assertUnprocessable()->assertJsonValidationErrors('status');

        $this->assertSame(FeedbackStatus::Inactive, $feedback->fresh()->status);
    }

    /** The moderation page lists pending feedback by default and can show approved ones. */
    public function test_superadmin_feedback_page_filters_by_status(): void
    {
        $root = $this->superadmin();
        $this->feedback('Đang chờ duyệt', 5, 'inactive');
        $this->feedback('Đã được duyệt', 5, 'active');

        $this->actingAs($root, 'admin')->get(route('superadmin.feedbacks.page'))
            ->assertOk()->assertSee('Đang chờ duyệt')->assertDontSee('Đã được duyệt');
        $this->actingAs($root, 'admin')->get(route('superadmin.feedbacks.page', ['status' => 'active']))
            ->assertOk()->assertSee('Đã được duyệt')->assertDontSee('Đang chờ duyệt');
        $this->actingAs($root, 'admin')->get(route('superadmin.feedbacks.page', ['status' => 'all']))
            ->assertOk()->assertSee('Đang chờ duyệt')->assertSee('Đã được duyệt');
    }

    /** The moderation table shows icon actions per status and an empty state when nothing matches. */
    public function test_superadmin_feedback_page_renders_actions_and_empty_states(): void
    {
        $root = $this->superadmin();

        $this->actingAs($root, 'admin')->get(route('superadmin.feedbacks.page'))
            ->assertOk()->assertSee(__('superadmin.feedbacks.no_pending_title'));

        $pending = $this->feedback('Chờ duyệt', 4, 'inactive');
        $approved = $this->feedback('Đã duyệt', 5, 'active');

        $this->actingAs($root, 'admin')->get(route('superadmin.feedbacks.page', ['status' => 'all']))
            ->assertOk()
            ->assertSee('data-feedback-id="'.$pending->id.'" data-status="active"', false)
            ->assertSee('data-feedback-id="'.$approved->id.'" data-status="inactive"', false)
            ->assertSee(__('superadmin.feedbacks.rating_value', ['rating' => 4]));

        $this->actingAs($root, 'admin')->get(route('superadmin.feedbacks.page', ['status' => 'all', 'q' => 'không-tồn-tại']))
            ->assertOk()->assertSee(__('superadmin.feedbacks.empty_title'));
    }

    private function user(string $email): GlobalUser
    {
        return GlobalUser::create(['name' => 'Feedback User', 'normalized_name' => 'FEEDBACK USER', 'email' => $email, 'status' => 'active']);
    }

    private function superadmin(): AdminAccount
    {
        return AdminAccount::create(['name' => 'Root', 'email' => 'root@drinkflow.test', 'password' => 'password123', 'role' => AdminRole::SuperAdmin, 'status' => 'active']);
    }

    private function feedback(string $content, int $rating, string $status): Feedback
    {
        return Feedback::forceCreate([
            'rating' => $rating,
            'subsystem' => 'all',
            'content' => $content,
            'status' => $status,
            'user_display_name' => 'Tester',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
