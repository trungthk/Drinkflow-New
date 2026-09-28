<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VersionPageTest extends TestCase
{
    use RefreshDatabase;
    public function test_versions_index_loads_latest_version(): void
    {
        $response = $this->get('/versions');

        $response->assertStatus(200);
        $response->assertSee('v2.3.0');
        $response->assertSee('Lịch sử cập nhật');
        $response->assertSee('Tính năng mới (New Features)');
        $response->assertSee('Cải tiến &amp; Tối ưu (Improvements)', false);
        $response->assertSee('Sửa lỗi (Bug Fixes)');
        $response->assertSee('Bảo mật &amp; Quản trị (Security &amp; Governance)', false);
        $response->assertSee('Chưa có (Bản mới nhất)');
        $response->assertSee('Phiên bản trước');
        $response->assertSee('go-to-top-btn');
    }

    public function test_versions_specific_valid_version_loads(): void
    {
        $response = $this->get('/versions/v2.2.1');

        $response->assertStatus(200);
        $response->assertSee('v2.2.1');
        $response->assertSee('Sửa lỗi Socket.IO reconnection &amp; tối ưu UI bàn chốt đơn', false);
        $response->assertSee('Phiên bản kế tiếp');
        $response->assertSee('Phiên bản trước');
        $response->assertSee('go-to-top-btn');
    }

    public function test_versions_invalid_version_returns_404(): void
    {
        $response = $this->get('/versions/v9.9.9-invalid');

        $response->assertStatus(404);
    }

    /**
     * A stored release's changelog is rendered as Markdown (raw HTML escaped) and no placeholder summary is shown.
     */
    public function test_database_release_changelog_renders_markdown_without_placeholder_summary(): void
    {
        \App\Models\Version::create([
            'version' => 'v2.3.1',
            'title' => 'Bản cập nhật tháng 9',
            'changelog' => "## Có gì mới\n\n- **Đặt dùm** cho thành viên\n- Sửa lỗi <script>alert(1)</script>",
            'release_date' => '2026-09-23',
        ]);

        $response = $this->get('/versions/v2.3.1')->assertOk();

        $response->assertSee('<h2>Có gì mới</h2>', false);
        $response->assertSee('<strong>Đặt dùm</strong>', false);
        $response->assertSee('&lt;script&gt;', false);
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertDontSee('## Có gì mới');
        $response->assertDontSee('Thông tin chi tiết về bản cập nhật DrinkFlow');
    }

    /**
     * The versions menu is cached in the versions store and the key is cleared on create, update and delete.
     */
    public function test_versions_menu_cache_is_cleared_when_versions_change(): void
    {
        $store = \Illuminate\Support\Facades\Cache::store(config('cache.versions_store'));
        $key = \App\Models\Version::MENU_CACHE_KEY;

        $version = \App\Models\Version::create(['version' => 'v2.3.1', 'title' => 'A', 'changelog' => 'x', 'release_date' => '2026-09-23']);
        $this->get('/versions')->assertOk();
        $this->assertSame('v2.3.1', $store->get($key)[0]['version']);

        \App\Models\Version::create(['version' => 'v2.3.2', 'title' => 'B', 'changelog' => 'y', 'release_date' => '2026-09-27']);
        $this->assertFalse($store->has($key), 'Creating a version clears the menu cache.');
        $this->get('/versions')->assertOk()->assertSee('v2.3.2');
        $this->assertSame(['v2.3.2', 'v2.3.1'], array_column($store->get($key), 'version'));

        $version->update(['title' => 'A (sửa)']);
        $this->assertFalse($store->has($key), 'Updating a version clears the menu cache.');
        $this->get('/versions/v2.3.1')->assertOk()->assertSee('A (sửa)');

        $version->delete();
        $this->assertFalse($store->has($key), 'Deleting a version clears the menu cache.');
        $this->get('/versions/v2.3.1')->assertNotFound();
    }

    public function test_authenticated_user_sees_get_started_linking_to_me_dashboard(): void
    {
        $user = \App\Models\GlobalUser::firstOrCreate(
            ['email' => 'version-member@company.com'],
            [
                'name' => 'Version Member',
                'normalized_name' => 'VERSION MEMBER',
            ]
        );

        $response = $this->actingAs($user, 'web')->get('/versions');
        $response->assertStatus(200);
        $response->assertSee(route('user.me.dashboard'));
    }
}
