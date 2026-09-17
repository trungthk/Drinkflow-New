<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignItemTopping extends Model
{
    protected $fillable = ['campaign_item_id', 'name', 'price', 'status', 'sort_order'];

    public function campaignItem(): BelongsTo
    {
        return $this->belongsTo(CampaignItem::class);
    }
}
