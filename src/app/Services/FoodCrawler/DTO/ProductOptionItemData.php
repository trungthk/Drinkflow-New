<?php

declare(strict_types=1);

namespace App\Services\FoodCrawler\DTO;

final class ProductOptionItemData
{
    public function __construct(
        public readonly string $externalId,
        public readonly string $name,
        public readonly int $additionalPrice = 0,
        public readonly bool $isDefault = false,
        public readonly int $maxQuantity = 1,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'external_id' => $this->externalId,
            'name' => $this->name,
            'additional_price' => $this->additionalPrice,
            'is_default' => $this->isDefault,
            'max_quantity' => $this->maxQuantity,
        ];
    }
}
