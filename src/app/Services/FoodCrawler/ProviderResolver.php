<?php

declare(strict_types=1);

namespace App\Services\FoodCrawler;

use App\Services\FoodCrawler\Contracts\FoodCrawlerProviderInterface;
use App\Services\FoodCrawler\Exceptions\UnsupportedFoodProviderException;

final class ProviderResolver
{
    /** @param iterable<FoodCrawlerProviderInterface> $providers */
    public function __construct(private readonly iterable $providers)
    {
    }

    /**
     * Resolve a provider for a source URL.
     *
     * @param string $url Source URL.
     * @return FoodCrawlerProviderInterface Matching provider.
     */
    public function resolve(string $url): FoodCrawlerProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($url)) {
                return $provider;
            }
        }

        throw new UnsupportedFoodProviderException('Food crawler provider is not supported for this URL.');
    }
}
