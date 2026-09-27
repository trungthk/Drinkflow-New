<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\GlobalUser;
use App\Services\Guide\UserGuideService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuideImageLightboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_guide_detail_pages_render_translated_image_lightbox(): void
    {
        $articles = app(UserGuideService::class)->list();
        if ($articles === []) {
            $this->markTestSkipped('No guide articles available.');
        }
        $slug = $articles[0]['slug'];
        $user = GlobalUser::create(['name' => 'Guide Reader', 'email' => 'guide-reader@example.test', 'status' => 'active']);

        $this->actingAs($user, 'web')->get(route('user.me.guides.show', $slug))
            ->assertOk()
            ->assertSee('data-image-lightbox', false)
            ->assertSee('data-image-lightbox-prev', false)
            ->assertSee('data-image-lightbox-next', false)
            ->assertSee(__('guides.lightbox_title'))
            ->assertSee(__('global.lightbox.close'))
            ->assertSee(__('global.lightbox.next'));
    }
}
