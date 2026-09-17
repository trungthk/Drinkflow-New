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
        $host = (string) $parts['host'];
        $ips = gethostbynamelist($host) ?: [];
        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                throw new FoodCrawlerException('The crawler URL must point to a public host.');
            }
        }

        return $this->resolver->resolve($url)->crawl($url);
    }
}
