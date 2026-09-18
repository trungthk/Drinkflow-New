<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignParticipant extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_DECLINED = 'declined';

    protected $fillable = ['campaign_id', 'room_user_id', 'status', 'declined_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['declined_at' => 'datetime'];
    }

    /** @return BelongsTo<Campaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /** @return BelongsTo<RoomUser, $this> */
    public function roomUser(): BelongsTo
    {
        return $this->belongsTo(RoomUser::class);
    }
}
