<?php

namespace App\Actions\Campaign;

use App\Models\CampaignItem;
use Illuminate\Support\Facades\DB;

class UpdateCampaignItemAction
{
    public function execute(CampaignItem $item, array $data): CampaignItem
    {
        return DB::transaction(function () use ($item, $data) {
            if (isset($data['name'])) {
                $data['normalized_name'] = strtoupper(trim(preg_replace('/\s+/', ' ', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $data['name']) ?: $data['name'])));
            } $item->update($data);

            return $item->fresh();
        });
    }
}
