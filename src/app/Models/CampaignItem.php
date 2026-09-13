<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CampaignItemStatus;
use App\Models\Concerns\BelongsToRoom;
use App\Models\Concerns\HasNormalizedName;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignItem extends Model
{
    use HasStatus, HasNormalizedName, BelongsToRoom;

    protected $fillable = [
        'campaign_id',
        'name',
        'normalized_name',
        'category',
        'description',
        'image_url',
        'base_price',
        'status',
        'sort_order',
        'source_url',
        'source_item_key',
    ];

    protected function casts(): array
    {
        return [
            'status'     => CampaignItemStatus::class,
            'base_price' => 'decimal:0',
            'sort_order' => 'integer',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function toppings(): HasMany
    {
        return $this->hasMany(CampaignItemTopping::class);
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(CampaignItemSize::class);
    }
}
