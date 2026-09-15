<?php

declare(strict_types=1);

namespace App\Services\FoodCrawler\Contracts;

interface BrowserTransportInterface
{
    /**
     * Load a public page in a real browser and return its rendered HTML.
     *
     * @param string $url Public page URL.
     * @return string Rendered HTML.
     */
    public function load(string $url): string;

    /**
     * Capture public API responses for browser diagnostics.
     *
     * @param string $url Public page URL.
     * @return array<string, mixed> Captured browser diagnostics.
     */
    public function networkData(string $url): array;
}
