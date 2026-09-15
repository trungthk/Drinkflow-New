<?php

declare(strict_types=1);

namespace App\Services\FoodCrawler\DTO;

final class MenuProductData
{
    /** @param array<int, ProductOptionGroupData> $optionGroups */
    public function __construct(
        public readonly string $externalId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly int $price,
        public readonly ?int $discountPrice,
        public readonly int $sellingPrice,
        public readonly ?string $imageUrl,
        public readonly bool $isActive,
        public readonly bool $isAvailable,
        public readonly int $displayOrder,
        public readonly array $optionGroups = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'external_id' => $this->externalId,
            'name' => $this->name,
            'description' => $this->description,
            'base_price' => $this->sellingPrice,
            'original_price' => $this->price,
            'discount_price' => $this->discountPrice,
            'image_url' => $this->imageUrl,
            'is_active' => $this->isActive,
            'is_available' => $this->isAvailable,
            'sort_order' => $this->displayOrder,
            'option_groups' => array_map(static fn (ProductOptionGroupData $group): array => $group->toArray(), $this->optionGroups),
            'source_item_key' => $this->externalId,
        ];
    }
}
