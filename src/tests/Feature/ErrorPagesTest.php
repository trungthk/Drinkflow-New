<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_404_page_renders_with_public_layout_and_translations(): void
    {
        $response = $this->get('/non-existent-page-url-' . uniqid());
        $response->assertStatus(404);
        $response->assertSee('404 - NOT FOUND');
        $response->assertSee('DrinkFlow');
    }

    public function test_404_page_renders_in_english_and_japanese(): void
    {
        app()->setLocale('en');
        $enView = view('errors.404')->render();
        $this->assertStringContainsString('HTTP ERROR CODE: 404 - NOT FOUND', $enView);

        app()->setLocale('ja');
        $jaView = view('errors.404')->render();
        $this->assertStringContainsString('HTTPエラーコード: 404 - NOT FOUND', $jaView);

        app()->setLocale('vi');
    }

    public function test_404_from_missing_route_model_uses_session_locale(): void
    {
        foreach (['en' => 'HTTP ERROR CODE: 404 - NOT FOUND', 'ja' => 'HTTPエラーコード: 404 - NOT FOUND'] as $locale => $badge) {
            $response = $this->withSession(['locale' => $locale])->get('/rooms/missing-room-' . uniqid() . '/join');

            $response->assertStatus(404);
            $response->assertSee($badge);
            $response->assertSee('lang="' . $locale . '"', false);
        }
    }

    public function test_error_views_can_be_rendered_directly(): void
    {
        $view403 = view('errors.403')->render();
        $this->assertStringContainsString('403', $view403);
        $this->assertStringContainsString('ACCESS FORBIDDEN', $view403);

        $view500 = view('errors.500')->render();
        $this->assertStringContainsString('500', $view500);
        $this->assertStringContainsString('INTERNAL SERVER ERROR', $view500);

        $view429 = view('errors.429')->render();
        $this->assertStringContainsString('429', $view429);
        $this->assertStringContainsString('RATE LIMIT EXCEEDED', $view429);

        $view503 = view('errors.503')->render();
        $this->assertStringContainsString('503', $view503);
        $this->assertStringContainsString('SERVICE UNAVAILABLE', $view503);

        $view419 = view('errors.419')->render();
        $this->assertStringContainsString('419', $view419);
        $this->assertStringContainsString('PAGE EXPIRED', $view419);
    }
}
