<?php

declare(strict_types=1);

namespace App\Actions\Campaign;

use App\Models\Campaign;
use App\Models\CampaignItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateCampaignItemAction
{
    /**
     * Create a menu item for a campaign.
     *
     * @param Campaign $campaign Parent campaign entity.
     * @param array<string, mixed> $data Item data.
     * @return CampaignItem Created item entity.
     * @throws ValidationException If campaign is closed or cancelled.
     */
    public function execute(Campaign $campaign, array $data): CampaignItem
    {
        if ($campaign->status?->value === 'closed' || $campaign->status?->value === 'cancelled') {
            throw ValidationException::withMessages([
                'campaign' => __('admin.campaign_closed'),
            ]);
        }

        return DB::transaction(fn (): CampaignItem => $campaign->items()->create(array_merge($data, [
            'normalized_name' => $this->normalize($data['name']),
            'status' => $data['status'] ?? 'active',
        ])));
    }

    /**
     * Normalize item name for consistent search and fuzzy matching.
     *
     * @param string $value Raw item name.
     * @return string Uppercase normalized ASCII representation.
     */
    private function normalize(string $value): string
    {
        return strtoupper(trim((string) preg_replace('/\s+/', ' ', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value)));
    }
}

