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
