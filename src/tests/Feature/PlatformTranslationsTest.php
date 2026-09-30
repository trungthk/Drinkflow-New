<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Arr;
use Tests\TestCase;

/**
 * Every SaaS platform string exists in vi, en and ja.
 */
class PlatformTranslationsTest extends TestCase
{
    public function test_platform_translation_keys_match_across_locales(): void
    {
        $keys = [];
        foreach (['vi', 'en', 'ja'] as $locale) {
            $keys[$locale] = array_keys(Arr::dot(require lang_path("{$locale}/platform.php")));
            sort($keys[$locale]);
        }

        $this->assertSame($keys['vi'], $keys['en'], 'lang/en/platform.php differs from vi');
        $this->assertSame($keys['vi'], $keys['ja'], 'lang/ja/platform.php differs from vi');
    }
}
