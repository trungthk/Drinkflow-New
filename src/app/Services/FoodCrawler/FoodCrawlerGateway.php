<?php

declare(strict_types=1);

namespace App\Services\FoodCrawler;

use App\Services\FoodCrawler\DTO\RestaurantMenuData;
use App\Services\FoodCrawler\Exceptions\FoodCrawlerException;
use App\Support\Security\OutboundUrlGuard;
use InvalidArgumentException;

final class FoodCrawlerGateway
{
    public function __construct(private readonly ProviderResolver $resolver, private readonly OutboundUrlGuard $guard)
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
        $url = trim($url);
        $parts = parse_url($url);
        if (! is_array($parts) || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true) || ! isset($parts['host'])) {
            throw new FoodCrawlerException('The crawler URL must use HTTP or HTTPS.');
        }

        // Resolve the provider first: its host allowlist is the primary SSRF control.
        $provider = $this->resolver->resolve($url);

        // Only HTTPS is crawled; a plain-HTTP link is upgraded so the guard below sees a single scheme.
        $url = (string) preg_replace('#^http://#i', 'https://', $url);
        try {
            $this->guard->assertSafe($url);
        } catch (InvalidArgumentException) {
            throw new FoodCrawlerException('The crawler URL must point to a public host.');
        }

        return $provider->crawl($url);
    }
}
