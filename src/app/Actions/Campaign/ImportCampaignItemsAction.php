<?php

namespace App\Actions\Campaign;

use App\Models\Campaign;
use Illuminate\Support\Facades\DB;

class ImportCampaignItemsAction
{
    public function execute(Campaign $campaign, string $sourceUrl, array $items): array
    {
        return DB::transaction(function () use ($campaign, $sourceUrl, $items): array {
            return collect($items)->map(function (array $item) use ($campaign, $sourceUrl): object {
                $name = trim($item['name']);
                return $campaign->items()->create([
                    'name' => $name,
                    'normalized_name' => $this->normalize($name),
                    'category' => $item['category'] ?? null,
                    'description' => $item['description'] ?? null,
                    'image_url' => $item['image_url'] ?? null,
                    'base_price' => (int) $item['base_price'],
                    'status' => 'active',
                    'sort_order' => (int) ($item['sort_order'] ?? 0),
                    'source_url' => $sourceUrl,
                    'source_item_key' => $item['source_item_key'] ?? null,
                ]);
            })->all();
        });
    }

    private function normalize(string $value): string
    {
        return strtoupper(trim(preg_replace('/\s+/', ' ', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value)));
    }
}
