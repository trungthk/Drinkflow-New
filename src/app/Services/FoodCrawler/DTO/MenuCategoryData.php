<?php

declare(strict_types=1);

namespace App\Services\FoodCrawler\DTO;

final class MenuCategoryData
{
    /** @param array<int, MenuProductData> $products */
    public function __construct(
        public readonly string $externalId,
        public readonly string $name,
        public readonly int $displayOrder,
        public readonly array $products = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'external_id' => $this->externalId,
            'name' => $this->name,
            'display_order' => $this->displayOrder,
            'products' => array_map(static fn (MenuProductData $product): array => $product->toArray(), $this->products),
        ];
    }
}
