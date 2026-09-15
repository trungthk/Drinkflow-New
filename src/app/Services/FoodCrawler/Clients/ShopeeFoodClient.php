<?php

declare(strict_types=1);

namespace App\Services\FoodCrawler\Clients;

use App\Services\FoodCrawler\Contracts\BrowserTransportInterface;

final class ShopeeFoodClient
{
    public function __construct(private readonly BrowserTransportInterface $browser)
    {
    }

    /**
     * Load the public ShopeeFood restaurant page in a browser.
     *
     * @param string $url Public restaurant URL.
     * @return string Rendered page HTML.
     */
    public function page(string $url): string
    {
        return $this->browser->load($url);
    }

}
