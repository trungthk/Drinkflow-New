<?php

declare(strict_types=1);

namespace App\Services\DataGateway;

use InvalidArgumentException;

/**
 * Convert a raw ShopeeFood / GrabFood menu response (copied from DevTools) into the DrinkFlow menu
 * schema without an external AI agent.
 *
 * Mapping rules:
 * - Optional modifier groups (min select 0) become `toppings`.
 * - One required single-choice group becomes `options` (a size group first, otherwise the first group
 *   whose choices change the price). Other required groups are ignored and reported.
 * - Ice / sugar groups are ignored: DrinkFlow orders carry their own ice and sugar levels.
 * - Unavailable items and choices are skipped; an item listed in several categories is kept once.
 */
class InternalMenuAnalyzerService
{
    private const ICE_SUGAR_PATTERN = '/(đá|đường|ngọt|\bice\b|sugar|sweet)/iu';
    private const SIZE_PATTERN = '/(size|cỡ|kích\s*thước)/iu';

    /** @var array<string, true> Names of modifier groups left out of the menu, for the summary. */
    private array $ignoredGroups = [];

    /**
     * Detect the platform of a raw menu payload from its structure.
     *
     * @param array<mixed> $data Decoded origin JSON.
     * @return string|null Platform identifier, or null when the structure is unknown.
     */
    public function detectPlatform(array $data): ?string
    {
        if (is_array($data['merchant']['menu']['categories'] ?? null)) {
            return DataGatewayConverterService::PLATFORM_GRAB;
        }
        if (is_array($data['reply']['menu_infos'] ?? null) || is_array($data['menu_infos'] ?? null)) {
            return DataGatewayConverterService::PLATFORM_SHOPEE;
        }

        return null;
    }

    /**
     * Analyse a raw menu payload into DrinkFlow menu items.
     *
     * @param array<mixed> $data Decoded origin JSON.
     * @param string|null $platform Platform chosen by the admin; the detected structure wins when they differ.
     * @return array{platform: string, platform_mismatch: bool, restaurant: ?string, items: list<array<string, mixed>>, summary: array{item_count: int, category_count: int, skipped_unavailable: int, skipped_duplicates: int, ignored_groups: list<string>}} Menu items and analysis summary.
     *
     * @throws InvalidArgumentException When the payload is neither a ShopeeFood nor a GrabFood menu.
     */
    public function analyze(array $data, ?string $platform = null): array
    {
        $detected = $this->detectPlatform($data) ?? throw new InvalidArgumentException('Unsupported menu structure.');
        $this->ignoredGroups = [];

        $result = $detected === DataGatewayConverterService::PLATFORM_GRAB
            ? $this->fromGrab($data)
            : $this->fromShopee($data);

        return [
            'platform' => $detected,
            'platform_mismatch' => $platform !== null && $platform !== $detected,
            'restaurant' => $result['restaurant'],
            'items' => $result['items'],
            'summary' => [
                'item_count' => count($result['items']),
                'category_count' => count(array_unique(array_column($result['items'], 'category'))),
                'skipped_unavailable' => $result['skipped_unavailable'],
                'skipped_duplicates' => $result['skipped_duplicates'],
                'ignored_groups' => array_keys($this->ignoredGroups),
            ],
        ];
    }

    /**
     * Map a GrabFood merchant response. Virtual categories (e.g. "Today's deals", "For you", which have a
     * categoryType) repeat items of the real categories, so real categories are read first.
     *
     * @param array<mixed> $data Decoded GrabFood merchant response.
     * @return array{restaurant: ?string, items: list<array<string, mixed>>, skipped_unavailable: int, skipped_duplicates: int}
     */
    private function fromGrab(array $data): array
    {
        $categories = array_filter((array) $data['merchant']['menu']['categories'], 'is_array');
        usort($categories, static fn (array $a, array $b): int => (int) isset($a['categoryType']) <=> (int) isset($b['categoryType']));

        $items = [];
        $seen = [];
        $skippedUnavailable = 0;
        $skippedDuplicates = 0;

        foreach ($categories as $category) {
            $categoryName = $this->text($category['name'] ?? '');
            foreach (array_filter((array) ($category['items'] ?? []), 'is_array') as $raw) {
                $key = (string) ($raw['ID'] ?? $this->text($raw['name'] ?? ''));
                if (isset($seen[$key])) {
                    $skippedDuplicates++;
                    continue;
                }
                $seen[$key] = true;

                if (($raw['available'] ?? true) === false) {
                    $skippedUnavailable++;
                    continue;
                }

                $discounted = (int) ($raw['discountedPriceInMin'] ?? 0);
                $groups = array_map(fn (array $group): array => [
                    'name' => $this->text($group['name'] ?? ''),
                    'min' => (int) ($group['selectionRangeMin'] ?? 0),
                    'max' => (int) ($group['selectionRangeMax'] ?? 0),
                    'choices' => array_values(array_map(fn (array $modifier): array => [
                        'name' => $this->text($modifier['name'] ?? ''),
                        'price' => max(0, (int) ($modifier['priceInMinorUnit'] ?? $modifier['priceV2']['amountInMinor'] ?? 0)),
                    ], array_filter((array) ($group['modifiers'] ?? []), static fn (mixed $m): bool => is_array($m) && ($m['available'] ?? true) !== false))),
                    'absolute' => false,
                ], array_filter((array) ($raw['modifierGroups'] ?? []), static fn (mixed $g): bool => is_array($g) && ($g['available'] ?? true) !== false));

                $items[] = $this->item(
                    $this->text($raw['name'] ?? ''),
                    $discounted > 0 ? $discounted : (int) ($raw['priceInMinorUnit'] ?? 0),
                    $categoryName,
                    $this->text($raw['description'] ?? ''),
                    (string) ($raw['imgHref'] ?? ($raw['images'][0] ?? '')),
                    $groups,
                );
            }
        }

        $restaurant = $this->text($data['merchant']['name'] ?? '');

        return [
            'restaurant' => $restaurant !== '' ? $restaurant : null,
            'items' => array_values(array_filter($items, static fn (array $item): bool => $item['name'] !== '')),
            'skipped_unavailable' => $skippedUnavailable,
            'skipped_duplicates' => $skippedDuplicates,
        ];
    }

    /**
     * Map a ShopeeFood get_delivery_dishes response.
     *
     * @param array<mixed> $data Decoded ShopeeFood response.
     * @return array{restaurant: ?string, items: list<array<string, mixed>>, skipped_unavailable: int, skipped_duplicates: int}
     */
    private function fromShopee(array $data): array
    {
        $menuInfos = (array) ($data['reply']['menu_infos'] ?? $data['menu_infos'] ?? []);
        $items = [];
        $seen = [];
        $skippedUnavailable = 0;
        $skippedDuplicates = 0;

        foreach (array_filter($menuInfos, 'is_array') as $category) {
            $categoryName = $this->text($category['dish_type_name'] ?? $category['name'] ?? '');
            foreach (array_filter((array) ($category['dishes'] ?? []), 'is_array') as $raw) {
                $key = (string) ($raw['id'] ?? $this->text($raw['name'] ?? ''));
                if (isset($seen[$key])) {
                    $skippedDuplicates++;
                    continue;
                }
                $seen[$key] = true;

                if (! ($raw['is_available'] ?? true) || ! ($raw['is_active'] ?? true) || ($raw['is_deleted'] ?? false)) {
                    $skippedUnavailable++;
                    continue;
                }

                $price = (int) ($raw['price']['value'] ?? 0);
                $discount = (int) ($raw['discount_price']['value'] ?? 0);
                $price = $discount > 0 ? $discount : $price;

                $groups = array_map(function (array $option) use ($price): array {
                    $choices = array_values(array_map(fn (array $choice): array => [
                        'name' => $this->text($choice['name'] ?? ''),
                        'price' => max(0, (int) ($choice['price']['value'] ?? 0)),
                    ], array_filter((array) ($option['option_items']['items'] ?? []), 'is_array')));
                    $choicePrices = array_column($choices, 'price');

                    return [
                        'name' => $this->text($option['name'] ?? ''),
                        'min' => (int) ($option['option_items']['min_select'] ?? ($option['mandatory'] ?? false ? 1 : 0)),
                        'max' => (int) ($option['option_items']['max_select'] ?? 0),
                        'choices' => $choices,
                        // e.g. "Half 1/2 Pizza": no free choice and the dish costs exactly one of the choices, so choice
                        // prices are totals, not deltas (a size group always has a 0đ base choice).
                        'absolute' => $choicePrices !== [] && min($choicePrices) > 0 && in_array($price, $choicePrices, true),
                    ];
                }, array_filter((array) ($raw['options'] ?? []), 'is_array'));

                $items[] = $this->item(
                    $this->text($raw['name'] ?? ''),
                    $price,
                    $categoryName,
                    $this->text($raw['description'] ?? ''),
                    $this->shopeePhoto($raw['photos'] ?? []),
                    $groups,
                );
            }
        }

        return [
            'restaurant' => null,
            'items' => array_values(array_filter($items, static fn (array $item): bool => $item['name'] !== '')),
            'skipped_unavailable' => $skippedUnavailable,
            'skipped_duplicates' => $skippedDuplicates,
        ];
    }

    /**
     * Build one DrinkFlow menu item, sorting its modifier groups into toppings and options.
     *
     * @param string $name Item name.
     * @param int $price Selling price in VND.
     * @param string $category Category name.
     * @param string $description Item description.
     * @param string $imageUrl Image URL.
     * @param list<array{name: string, min: int, max: int, choices: list<array{name: string, price: int}>, absolute: bool}> $groups Normalised modifier groups.
     * @return array{name: string, price: int, category: string, description: string, image_url: string, toppings: list<array{name: string, price: int}>, options: list<array{name: string, price_delta: int}>}
     */
    private function item(string $name, int $price, string $category, string $description, string $imageUrl, array $groups): array
    {
        $toppings = [];
        $optionGroup = null;

        foreach ($groups as $group) {
            if ($group['choices'] === [] || preg_match(self::ICE_SUGAR_PATTERN, $group['name'])) {
                $this->ignore($group['name']);
                continue;
            }

            if ($group['min'] === 0) {
                foreach ($group['choices'] as $choice) {
                    $toppings[mb_strtolower($choice['name'])] ??= ['name' => $choice['name'], 'price' => $choice['price']];
                }
                continue;
            }

            $isSingleChoice = $group['max'] <= 1;
            $changesPrice = count(array_unique(array_column($group['choices'], 'price'))) > 1
                || ($group['choices'][0]['price'] ?? 0) > 0;
            $isSize = (bool) preg_match(self::SIZE_PATTERN, $group['name']);
            $replacesNonSize = $optionGroup !== null && ! $optionGroup['is_size'] && $isSize;

            if ($isSingleChoice && ($isSize || $changesPrice) && ($optionGroup === null || $replacesNonSize)) {
                if ($optionGroup !== null) {
                    $this->ignore($optionGroup['name']);
                }
                $optionGroup = $group + ['is_size' => $isSize];
                continue;
            }

            $this->ignore($group['name']);
        }

        $options = [];
        if ($optionGroup !== null) {
            if ($optionGroup['absolute']) {
                // Totals: the cheapest choice becomes the item price and every choice a delta on top of it.
                $price = min(array_column($optionGroup['choices'], 'price'));
            }
            foreach ($optionGroup['choices'] as $choice) {
                $options[] = [
                    'name' => $choice['name'],
                    'price_delta' => $optionGroup['absolute'] ? $choice['price'] - $price : $choice['price'],
                ];
            }
        }

        return [
            'name' => $name,
            'price' => max(0, $price),
            'category' => $category,
            'description' => $description,
            'image_url' => filter_var($imageUrl, FILTER_VALIDATE_URL) ? $imageUrl : '',
            'toppings' => array_values($toppings),
            'options' => $options,
        ];
    }

    /**
     * Pick the largest ShopeeFood photo.
     *
     * @param mixed $photos Raw `photos` list ({value|url, width}).
     * @return string Image URL or an empty string.
     */
    private function shopeePhoto(mixed $photos): string
    {
        $best = '';
        $bestWidth = -1;
        foreach (is_array($photos) ? $photos : [] as $photo) {
            $url = is_array($photo) ? (string) ($photo['value'] ?? $photo['url'] ?? '') : (string) $photo;
            $width = is_array($photo) ? (int) ($photo['width'] ?? 0) : 0;
            if ($url !== '' && $width > $bestWidth) {
                [$best, $bestWidth] = [$url, $width];
            }
        }

        return $best;
    }

    /**
     * Remember a modifier group that was left out of the menu.
     *
     * @param string $name Group name.
     * @return void
     */
    private function ignore(string $name): void
    {
        if ($name !== '') {
            $this->ignoredGroups[$name] = true;
        }
    }

    /**
     * Collapse whitespace in a raw text value.
     *
     * @param mixed $value Raw value.
     * @return string Trimmed single-line text.
     */
    private function text(mixed $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', is_scalar($value) ? (string) $value : ''));
    }
}
