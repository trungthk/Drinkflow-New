<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * robots.txt must block private areas and advertise the sitemap.
     */
    public function test_robots_txt_lists_rules_and_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertSee('Disallow: /admin', false);
        $response->assertSee('Sitemap: '.url('/sitemap.xml'), false);
    }

    /**
     * sitemap.xml must list the public pages only.
     */
    public function test_sitemap_lists_public_pages_only(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertSee('<loc>'.url('/').'</loc>', false);
        $response->assertSee('<loc>'.url('/terms').'</loc>', false);
        $response->assertSee('<loc>'.url('/en/terms').'</loc>', false);
        $response->assertSee('<loc>'.url('/ja').'</loc>', false);
        $response->assertSee('hreflang="x-default"', false);
        $response->assertDontSee('/admin', false);
        $this->assertStringContainsString('xml', (string) $response->headers->get('Content-Type'));
    }

    /**
     * Private and tokenised URLs carry a noindex header; public pages do not.
     */
    public function test_private_routes_send_noindex_header(): void
    {
        $this->assertStringContainsString('noindex', (string) $this->get('/admin/login')->headers->get('X-Robots-Tag'));
        $this->assertStringContainsString('noindex', (string) $this->get('/rooms/missing')->headers->get('X-Robots-Tag'));
        $this->assertNull($this->get('/')->headers->get('X-Robots-Tag'));
    }

    /**
     * The landing page exposes a raster og:image and an indexable robots meta tag.
     */
    public function test_landing_page_meta(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<meta name="robots" content="index, follow"/>', false)
            ->assertSee('images/home-intro.webp', false);
    }

    /**
     * Language-prefixed URLs render the matching language with hreflang alternates and self canonical.
     */
    public function test_prefixed_language_urls_have_hreflang_and_canonical(): void
    {
        $this->get('/en')
            ->assertOk()
            ->assertSee('Faster Orders')
            ->assertSee('<link rel="canonical" href="'.url('/en').'"/>', false)
            ->assertSee('hreflang="vi" href="'.url('/').'"', false)
            ->assertSee('hreflang="ja" href="'.url('/ja').'"', false)
            ->assertSee('<html class="light h-full" lang="en">', false);

        $this->get('/ja/terms')->assertOk()->assertSee('hreflang="en" href="'.url('/en/terms').'"', false);
        $this->get('/en/contact')->assertOk();
        $this->get('/en/versions')->assertOk();
    }

    /**
     * The Vietnamese switch link resets the session locale and lands on the clean URL.
     */
    public function test_locale_switch_can_redirect_to_clean_public_path(): void
    {
        $this->get('/lang/vi?to=/terms')->assertRedirect('/terms')->assertSessionHas('locale', 'vi');
        $this->get('/lang/vi?to=//evil.example')->assertRedirect();
    }

    /**
     * Public pages no longer load the Tailwind Play CDN and expose structured data.
     */
    public function test_public_layout_drops_tailwind_cdn_and_has_json_ld(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('cdn.tailwindcss.com', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('"@type":"Organization"', false);
    }

    /**
     * Public guides are indexable, listed in the sitemap, and sensitive articles stay hidden.
     */
    public function test_public_guides_are_available_and_in_sitemap(): void
    {
        $this->get('/guides')
            ->assertOk()
            ->assertSee('03-dat-mon-chien-dich', false)
            ->assertDontSee('08-bao-mat-thiet-bi', false)
            ->assertSee('"@type":"CollectionPage"', false);

        $this->get('/guides/03-dat-mon-chien-dich')
            ->assertOk()
            ->assertSee('guide-article', false)
            ->assertSee('"@type":"TechArticle"', false)
            ->assertSee('<link rel="canonical" href="'.url('/guides/03-dat-mon-chien-dich').'"/>', false);

        $this->get('/guides/08-bao-mat-thiet-bi')->assertNotFound();
        $this->get('/guides/README')->assertNotFound();

        $this->get('/sitemap.xml')
            ->assertSee('<loc>'.url('/guides').'</loc>', false)
            ->assertSee('<loc>'.url('/guides/03-dat-mon-chien-dich').'</loc>', false);
    }
}
