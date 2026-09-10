<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignItem extends Model
{
    protected $fillable = ['campaign_id', 'name', 'normalized_name', 'category', 'description', 'image_url', 'base_price', 'status', 'sort_order', 'source_url', 'source_item_key'];

    public function campaign(): BelongsTo { return $this->belongsTo(Campaign::class); }

    public function toppings(): HasMany { return $this->hasMany(CampaignItemTopping::class); }

    public function sizes(): HasMany { return $this->hasMany(CampaignItemSize::class); }
}
