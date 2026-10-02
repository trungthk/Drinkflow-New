<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Guide\UserGuideService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T77: the public guide pages serve two sets of articles side by side.
 *
 * `/guides` keeps serving the Client set, `/guides/agent` serves the Agent set, and both offer the
 * switch between them.
 */
class PublicAgentGuidesTest extends TestCase
{
    use RefreshDatabase;

    /** Client article that must stay public. */
    private const CLIENT_SLUG = '03-dat-mon-chien-dich';

    /** Agent article that must become public through the new pages. */
    private const AGENT_SLUG = '04-quan-ly-chien-dich';

    public function test_client_guides_stay_on_the_root_path(): void
    {
        $this->get('/guides')
            ->assertOk()
            ->assertSee(__('guides.page_title'))
            ->assertSee(url('/guides/'.self::CLIENT_SLUG), false)
            ->assertSee(__('guides.audience_agent'));

        $this->get('/guides/'.self::CLIENT_SLUG)
            ->assertOk()
            ->assertSee('guide-article', false)
            ->assertSee('<link rel="canonical" href="'.url('/guides/'.self::CLIENT_SLUG).'"/>', false);
    }

    public function test_agent_guides_are_served_on_their_own_path(): void
    {
        $this->get('/guides/agent')
            ->assertOk()
            ->assertSee(__('guides.agent_page_title'))
            ->assertSee(url('/guides/agent/'.self::AGENT_SLUG), false)
            ->assertSee(__('guides.audience_client'));

        $this->get('/guides/agent/'.self::AGENT_SLUG)
            ->assertOk()
            ->assertSee('guide-article', false)
            ->assertSee('<link rel="canonical" href="'.url('/guides/agent/'.self::AGENT_SLUG).'"/>', false);
    }

    public function test_the_two_sets_do_not_serve_each_others_slugs(): void
    {
        // A client slug has no agent article and the other way round: the sets stay separated.
        $this->get('/guides/agent/'.self::CLIENT_SLUG)->assertNotFound();
        $this->get('/guides/'.self::AGENT_SLUG)->assertNotFound();
    }

    public function test_index_and_readme_slugs_stay_hidden_in_both_sets(): void
    {
        $this->get('/guides/README')->assertNotFound();
        $this->get('/guides/agent/README')->assertNotFound();
        $this->get('/guides/agent/99-khong-ton-tai')->assertNotFound();
    }

    public function test_both_pages_offer_the_audience_switch(): void
    {
        foreach (['/guides', '/guides/agent'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee(url('/guides'), false)
                ->assertSee(url('/guides/agent'), false)
                ->assertSee(__('guides.audience_toggle_label'));
        }
    }

    public function test_the_guide_service_exposes_both_audiences(): void
    {
        $service = app(UserGuideService::class);

        $this->assertNotEmpty($service->list());
        $this->assertNotEmpty($service->list(UserGuideService::AUDIENCE_AGENT));
        $this->assertSame(UserGuideService::audiences(), [UserGuideService::AUDIENCE_CLIENT, UserGuideService::AUDIENCE_AGENT]);
    }

    public function test_agent_guide_images_are_lazy_loaded_and_rewritten(): void
    {
        $html = app(UserGuideService::class)->find(self::AGENT_SLUG, url('/guides/agent'), UserGuideService::AUDIENCE_AGENT)['html'] ?? '';

        $this->assertStringContainsString('/guide-content/admin/images/', $html);
        $this->assertStringNotContainsString('<img src="images/', $html);
        $this->assertStringNotContainsString('<img loading="lazy" src="images/', $html);
    }

    public function test_sitemap_lists_the_agent_guide_pages(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('<loc>'.url('/guides/agent').'</loc>', false)
            ->assertSee('<loc>'.url('/guides/agent/'.self::AGENT_SLUG).'</loc>', false);
    }

    public function test_landing_page_links_to_the_agent_registration_and_agent_guides(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('admin.register.page'), false)
            ->assertSee(url('/guides/agent'), false)
            ->assertSee(__('public.agent_program.cta_register'));
    }
}
