<?php

namespace Tests\Feature;

use App\Models\GlobalUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_locale_is_vietnamese_on_public_pages(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Order nhanh hơn');
        $response->assertSee('Bắt đầu sử dụng');
        $response->assertSee('public-page-loading');
        $response->assertSee('go-to-top-btn');
    }

    public function test_switching_locale_to_english_updates_session_and_renders_english(): void
    {
        // Switch locale to 'en'
        $switchResponse = $this->get(route('locale.switch', ['locale' => 'en']));
        $switchResponse->assertRedirect();
        $switchResponse->assertSessionHas('locale', 'en');

        // Access landing with English session
        $response = $this->withSession(['locale' => 'en'])->get('/');
        $response->assertStatus(200);
        $response->assertSee('Faster Orders');
        $response->assertSee('Get Started');
        $response->assertSee('public-page-loading');
    }

    public function test_switching_locale_to_japanese_updates_session_and_renders_japanese(): void
    {
        // Switch locale to 'ja'
        $switchResponse = $this->get(route('locale.switch', ['locale' => 'ja']));
        $switchResponse->assertRedirect();
        $switchResponse->assertSessionHas('locale', 'ja');

        // Access landing with Japanese session
        $response = $this->withSession(['locale' => 'ja'])->get('/');
        $response->assertStatus(200);
        $response->assertSee('DrinkFlow — より速く、スマートに、明確な割り勘を。');
        $response->assertSee('今すぐ始める');
    }

    public function test_invalid_locale_defaults_to_vietnamese(): void
    {
        $this->get('/lang/invalid_locale');

        $response = $this->withSession(['locale' => 'invalid_locale'])->get('/');
        $response->assertStatus(200);
        $response->assertSee('Order nhanh hơn');
    }

    public function test_terms_and_versions_contain_shared_header_and_loading(): void
    {
        $termsResponse = $this->get('/terms');
        $termsResponse->assertStatus(200);
        $termsResponse->assertSee('public-lang-selector');
        $termsResponse->assertSee('public-page-loading');
        $termsResponse->assertSee('go-to-top-btn');

        $versionsResponse = $this->get('/versions');
        $versionsResponse->assertStatus(200);
        $versionsResponse->assertSee('public-lang-selector');
        $versionsResponse->assertSee('public-page-loading');
        $versionsResponse->assertSee('go-to-top-btn');
    }

    public function test_global_user_pages_contain_loading_and_go_to_top(): void
    {
        $user = GlobalUser::create([
            'email' => 'user@company.com',
            'name' => 'User Test',
            'normalized_name' => 'USER TEST',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'web')->get('/me');
        $response->assertStatus(200);
        $response->assertSee('global-page-loading');
        $response->assertSee('global-go-to-top-btn');
    }
}
