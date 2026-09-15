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
     * Evaluate a script in the rendered page context.
     *
     * @param string $url Public page URL.
     * @param string $script JavaScript function source.
     * @return string Serialized evaluation result.
     */
    public function networkData(string $url): array;
}
