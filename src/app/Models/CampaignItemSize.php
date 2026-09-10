<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CampaignItemSize extends Model {
    protected $fillable = ['campaign_item_id','name','price_delta','status','sort_order'];
    public function campaignItem(): BelongsTo { return $this->belongsTo(CampaignItem::class); }
}
