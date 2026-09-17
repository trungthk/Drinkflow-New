<?php

declare(strict_types=1);

namespace App\Actions\Campaign;

use App\Models\CampaignItem;
use App\Services\Media\ImageUploadService;
use Illuminate\Support\Facades\DB;

class UpdateCampaignItemAction
{
    /**
     * Create the action instance.
     *
     * @param ImageUploadService $imageUploadService Image upload and storage cleanup service.
     */
    public function __construct(
        private readonly ImageUploadService $imageUploadService
    ) {
    }

    /**
     * Handle the execute operation for updating a single campaign item.
     *
     * @param CampaignItem $item Target campaign item to update.
     * @param array<string, mixed> $data Updated item attributes.
     * @return CampaignItem Freshly updated campaign item instance.
     */
    public function execute(CampaignItem $item, array $data): CampaignItem
    {
        return DB::transaction(function () use ($item, $data): CampaignItem {
            if (isset($data['name'])) {
                $data['normalized_name'] = strtoupper(trim(preg_replace('/\s+/', ' ', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $data['name']) ?: (string) $data['name'])));
            }

            if (array_key_exists('image_url', $data) && !empty($item->image_url) && $item->image_url !== $data['image_url']) {
                $this->imageUploadService->deleteFile($item->image_url);
            }

            $item->update($data);

            return $item->fresh();
        });
    }
}
