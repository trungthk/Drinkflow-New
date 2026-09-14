<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TermsPageTest extends TestCase
{
    use RefreshDatabase;
    public function test_terms_page_returns_successful_response(): void
    {
        $response = $this->get('/terms');

        $response->assertStatus(200);
        $response->assertSee('Điều khoản sử dụng DrinkFlow');
        $response->assertSee('Mục lục quy chuẩn');
        $response->assertSee('Phạm vi áp dụng &amp; Mục đích', false);
        $response->assertSee('Điều kiện sử dụng &amp; Quyền truy cập', false);
        $response->assertSee('Xác thực bảo mật Google OAuth');
        $response->assertSee('Phân định Global User &amp; Room User', false);
        $response->assertSee('Quy định Order &amp; Hủy đơn hàng', false);
        $response->assertSee('Biểu phí, Tách Bill &amp; Thanh toán VietQR', false);
        $response->assertSee('Quản lý Thiết bị tin cậy &amp; Khóa tài khoản', false);
        $response->assertSee('Trách nhiệm của Host &amp; Người tham gia', false);
        $response->assertSee('v1.0');
        $response->assertSee('go-to-top-btn');
    }

    public function test_named_route_terms_points_to_terms_url(): void
    {
        $this->assertEquals(url('/terms'), route('terms'));
    }

    public function test_authenticated_user_sees_get_started_linking_to_me_dashboard(): void
    {
        $user = \App\Models\GlobalUser::firstOrCreate(
            ['email' => 'terms-member@company.com'],
            [
                'name' => 'Terms Member',
                'normalized_name' => 'TERMS MEMBER',
            ]
        );

        $response = $this->actingAs($user, 'web')->get('/terms');
        $response->assertStatus(200);
        $response->assertSee(route('user.me.dashboard'));
    }
}
