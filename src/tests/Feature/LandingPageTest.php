<?php

namespace Tests\Feature;

use App\Models\GlobalUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_returns_successful_response_for_guest(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('DrinkFlow');
        $response->assertSee('Order nhanh hơn');
        $response->assertSee('Quản lý campaign dễ hơn');
        $response->assertSee('Chia bill rõ ràng hơn');
        $response->assertSee('Vấn đề truyền thống vs Giải pháp DrinkFlow');
        $response->assertSee('Lợi ích cốt lõi cho văn phòng hiện đại');
        $response->assertSee('Quy trình đặt đơn 4 bước đơn giản');
        $response->assertSee('Bảo mật tài khoản doanh nghiệp với Google OAuth');
        $response->assertSee('Google Workspace Enterprise SSO');
        $response->assertSee('v2.3.0');
        $response->assertSee('Giới thiệu về DrinkFlow');
        $response->assertSee('about-drinkflow.mp4');
        $response->assertSee('go-to-top-btn');
        $response->assertDontSee('Live Session:');
        $response->assertDontSee('28 người đang đặt');
    }

    public function test_landing_page_does_not_redirect_authenticated_user(): void
    {
        $user = GlobalUser::firstOrCreate(
            ['email' => 'member@company.com'],
            [
                'name' => 'Member Test',
                'normalized_name' => 'MEMBER TEST',
            ]
        );

        $response = $this->actingAs($user, 'web')->get('/');

        // Per requirement: do not check or redirect logged in users; always render landing page
        $response->assertStatus(200);
        $response->assertSee('DrinkFlow');
    }

    public function test_named_route_landing_points_to_home(): void
    {
        $this->assertEquals(url('/'), route('landing'));
    }

    public function test_terms_and_versions_routes_are_defined(): void
    {
        $this->assertEquals(url('/terms'), route('terms'));
        $this->assertEquals(url('/versions'), route('versions'));
    }

    public function test_authenticated_user_sees_get_started_linking_to_me_dashboard(): void
    {
        $user = GlobalUser::create([
            'email' => 'member@company.com',
            'name' => 'Member Test',
            'normalized_name' => 'MEMBER TEST',
        ]);

        $response = $this->actingAs($user, 'web')->get('/');
        $response->assertStatus(200);
        $response->assertSee(route('user.me.dashboard'));
    }
}
