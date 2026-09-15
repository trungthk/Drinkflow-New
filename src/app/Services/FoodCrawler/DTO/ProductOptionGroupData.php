<?php

declare(strict_types=1);

namespace App\Services\FoodCrawler\DTO;

final class ProductOptionGroupData
{
    /** @param array<int, ProductOptionItemData> $items */
    public function __construct(
        public readonly string $externalId,
        public readonly string $name,
        public readonly bool $mandatory,
        public readonly int $minSelect,
        public readonly int $maxSelect,
        public readonly array $items = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'external_id' => $this->externalId,
            'name' => $this->name,
            'mandatory' => $this->mandatory,
            'min_select' => $this->minSelect,
            'max_select' => $this->maxSelect,
            'items' => array_map(static fn (ProductOptionItemData $item): array => $item->toArray(), $this->items),
        ];
    }
}
