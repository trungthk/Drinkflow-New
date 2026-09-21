<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class PaginationTranslationTest extends TestCase
{
    /**
     * The paginator labels and summary follow the active locale instead of staying in English.
     *
     * @return void
     */
    public function test_pagination_is_translated_for_every_locale(): void
    {
        $expected = [
            'vi' => ['Trước', 'Sau', 'Hiển thị 3 đến 4 trong tổng số 20 kết quả'],
            'en' => ['Previous', 'Next', 'Showing 3 to 4 of 20 results'],
            'ja' => ['前へ', '次へ', '全 20 件中 3～4 件を表示'],
        ];

        foreach ($expected as $locale => [$previous, $next, $summary]) {
            app()->setLocale($locale);
            $html = (string) (new LengthAwarePaginator([1, 2], 20, 2, 2, ['path' => '/items']))->links();
            $text = trim((string) preg_replace('/\s+/', ' ', strip_tags($html)));

            $this->assertStringContainsString($previous, $text, "[$locale] previous label");
            $this->assertStringContainsString($next, $text, "[$locale] next label");
            $this->assertStringContainsString($summary, $text, "[$locale] summary");
            $this->assertStringContainsString(
                'aria-label="'.__('pagination.navigation').'"',
                $html,
                "[$locale] navigation label"
            );
            $this->assertStringContainsString(__('pagination.go_to_page', ['page' => 1]), $html, "[$locale] page link label");
        }
    }

    /**
     * Every pagination key exists in all supported locales.
     *
     * @return void
     */
    public function test_pagination_keys_exist_in_every_locale(): void
    {
        foreach (['vi', 'en', 'ja'] as $locale) {
            foreach (['previous', 'next', 'navigation', 'go_to_page', 'summary', 'summary_count'] as $key) {
                $this->assertTrue(
                    app('translator')->has("pagination.$key", $locale, false),
                    "Missing pagination.$key for [$locale]"
                );
            }
        }
    }
}
