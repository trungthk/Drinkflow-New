<?php

declare(strict_types=1);

namespace App\Services\FoodCrawler;

use App\Services\FoodCrawler\DTO\RestaurantMenuData;
use App\Services\FoodCrawler\Exceptions\FoodCrawlerException;

final class FoodCrawlerGateway
{
    public function __construct(private readonly ProviderResolver $resolver)
    {
    }

    /**
     * Resolve a provider and crawl a normalized menu.
     *
     * @param string $url Source URL.
     * @return RestaurantMenuData Normalized menu.
     */
    public function crawl(string $url): RestaurantMenuData
    {
        $parts = parse_url(trim($url));
        if (! is_array($parts) || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true) || ! isset($parts['host'])) {
            throw new FoodCrawlerException('The crawler URL must use HTTP or HTTPS.');
        }

        return $this->resolver->resolve($url)->crawl($url);
    }
}
