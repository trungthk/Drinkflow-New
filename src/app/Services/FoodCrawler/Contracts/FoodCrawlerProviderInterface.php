<?php

declare(strict_types=1);

namespace App\Services\FoodCrawler\Contracts;

use App\Services\FoodCrawler\DTO\RestaurantMenuData;

interface FoodCrawlerProviderInterface
{
    /**
     * Determine whether this provider can handle the source URL.
     *
     * @param string $url Source URL.
     * @return bool Whether the provider supports the URL.
     */
    public function supports(string $url): bool;

    /**
     * Crawl and normalize a restaurant menu.
     *
     * @param string $url Source URL.
     * @return RestaurantMenuData Normalized menu.
     */
    public function crawl(string $url): RestaurantMenuData;
}
