<?php

declare(strict_types=1);

namespace App\Services\FoodCrawler\DTO;

final class RestaurantMenuData
{
    /** @param array<int, MenuCategoryData> $categories */
    public function __construct(
        public readonly string $provider,
        public readonly string $sourceUrl,
        public readonly string $externalRestaurantId,
        public readonly ?string $externalDeliveryId,
        public readonly array $categories = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'source_url' => $this->sourceUrl,
            'restaurant' => ['external_id' => $this->externalRestaurantId],
            'external_delivery_id' => $this->externalDeliveryId,
            'categories' => array_map(static fn (MenuCategoryData $category): array => $category->toArray(), $this->categories),
        ];
    }

    /** @return array<int, array<string, mixed>> Legacy campaign item shape. */
    public function items(): array
    {
        $items = [];
        foreach ($this->categories as $category) {
            foreach ($category->products as $product) {
                $item = $product->toArray();
                $item['category'] = $category->name;
                $items[] = $item;
            }
        }

        return $items;
    }
}
