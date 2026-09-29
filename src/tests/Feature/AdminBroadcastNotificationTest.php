<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminBroadcastNotificationTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name' => 'Room Admin',
            'email' => 'broadcast-admin@example.test',
            'password' => Hash::make('secret'),
            'status' => 'active',
        ]);
        $this->room = Room::create(['name' => 'Broadcast Room', 'slug' => 'broadcast-room', 'status' => 'active']);
        $this->admin->rooms()->attach($this->room);
    }

    /**
     * The modal marks required fields with a red asterisk and uses the primary submit button.
     */
    public function test_broadcast_modal_marks_required_fields_and_uses_primary_button(): void
    {
        $html = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard.page', $this->room->slug))
            ->assertOk()
            ->getContent();

        $modal = (string) str($html)->after('id="admin-broadcast-modal"')->before('Global Admin API Fetch Helper');

        $this->assertSame(2, substr_count($modal, '<span class="ml-0.5 text-red-600" aria-hidden="true">*</span>'));
        $this->assertStringContainsString('data-broadcast-error="title"', $modal);
        $this->assertMatchesRegularExpression('/<button type="submit" class="[^"]*bg-primary[^"]*text-white[^"]*">\s*<span class="material-symbols-outlined text-\[16px\]">send<\/span>\s*<span>'.preg_quote(__('admin.broadcast_confirm'), '/').'<\/span>/u', $modal);
        $this->assertStringNotContainsString('bg-secondary px-4 py-2', $modal);
    }

    /**
     * The backend rejects missing/whitespace titles and unsupported types, and accepts a valid payload.
     */
    public function test_broadcast_backend_validation(): void
    {
        $url = route('admin.notifications.broadcast', $this->room->slug);

        $this->actingAs($this->admin, 'admin')->postJson($url, ['type' => 'admin.broadcast'])
            ->assertStatus(422)->assertJsonValidationErrors('title');
        $this->actingAs($this->admin, 'admin')->postJson($url, ['type' => 'admin.broadcast', 'title' => '   '])
            ->assertStatus(422)->assertJsonValidationErrors('title');
        $this->actingAs($this->admin, 'admin')->postJson($url, ['title' => 'Hello'])
            ->assertStatus(422)->assertJsonValidationErrors('type');
        $this->actingAs($this->admin, 'admin')->postJson($url, ['type' => 'security.alert', 'title' => 'Hello'])
            ->assertStatus(422)->assertJsonValidationErrors('type');
        $this->actingAs($this->admin, 'admin')->postJson($url, ['type' => 'admin.broadcast', 'title' => str_repeat('a', 161)])
            ->assertStatus(422)->assertJsonValidationErrors('title');

        $this->actingAs($this->admin, 'admin')->postJson($url, ['type' => 'payment.reminder', 'title' => 'Nhắc thanh toán', 'body' => 'Vui lòng thanh toán'])
            ->assertOk()->assertJsonStructure(['message', 'count']);
    }

    /**
     * The admin initials avatar uses the primary colour and the sidebar toggle carries a tooltip.
     */
    public function test_layout_avatar_and_sidebar_toggle_tooltip(): void
    {
        $html = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard.page', $this->room->slug))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/data-admin-avatar="sidebar"\s+class="[^"]*bg-primary text-white[^"]*"/', $html);
        $this->assertStringNotContainsString('bg-secondary text-on-secondary font-mono', $html);
        $this->assertStringContainsString('data-tooltip="'.__('admin.toggle_sidebar').'"', $html);
        $this->assertStringNotContainsString('title="'.__('admin.toggle_sidebar').'"', $html);
    }

    /**
     * The redesigned modal offers type cards, character counters and a live preview, and the dashboard
     * participation bar is colored by band in dashboard.js.
     */
    public function test_redesigned_broadcast_modal_and_participation_bar(): void
    {
        $html = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard.page', $this->room->slug))
            ->assertOk()
            ->assertSee(__('admin.broadcast_subtitle', ['room' => 'Broadcast Room']))
            ->assertSee(__('admin.broadcast_preview'))
            ->getContent();

        $modal = (string) str($html)->after('id="admin-broadcast-modal"')->before('Global Admin API Fetch Helper');
        foreach (['admin.broadcast', 'campaign.created', 'payment.reminder'] as $type) {
            $this->assertStringContainsString('data-broadcast-type-card="'.$type.'"', $modal);
            // Every card still maps to an option of the validated select.
            $this->assertStringContainsString('<option value="'.$type.'"', $modal);
        }
        $this->assertStringContainsString('data-broadcast-count="title"', $modal);
        $this->assertStringContainsString('data-broadcast-preview-title', $modal);

        $this->assertStringContainsString('id="hero-progress-bar"', $html);
        $script = file_get_contents(resource_path('js/admin/dashboard.js'));
        $this->assertStringContainsString("percent < 50 ? 'low' : (percent < 80 ? 'medium' : 'high')", $script);
    }
}
