<?php

namespace App\Actions\Campaign;

use App\Models\Campaign;
use App\Models\CampaignItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateCampaignItemAction
{
    /**
     * Handle the execute operation.
     * @param Campaign $campaign Parameter value.
     * @param array $data Parameter value.
     * @return CampaignItem Result of the operation.
     */
    public function execute(Campaign $campaign, array $data): CampaignItem
    {
        if ($campaign->status?->value === 'closed' || $campaign->status?->value === 'cancelled') {
            throw ValidationException::withMessages(['campaign' => 'Campaign Ä‘Ă£ Ä‘Ă³ng.']);
        }

        return DB::transaction(fn() => $campaign->items()->create(array_merge($data, ['normalized_name' => $this->normalize($data['name']), 'status' => $data['status'] ?? 'active'])));
    }

    /**
     * Handle the normalize operation.
     * @param string $value Parameter value.
     * @return string Result of the operation.
     */
    private function normalize(string $value): string
    {
        return strtoupper(trim(preg_replace('/\s+/', ' ', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value)));
    }
}
