<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\DebtStatus;

class Debt extends Model
{
    protected $fillable = ['room_id', 'campaign_id', 'room_user_id', 'original_amount', 'sponsor_amount', 'adjustment_amount', 'paid_amount', 'remaining_amount', 'status', 'note'];

    protected function casts(): array { return ['status' => DebtStatus::class]; }

    public function room(): BelongsTo { return $this->belongsTo(Room::class); }

    public function campaign(): BelongsTo { return $this->belongsTo(Campaign::class); }

    public function roomUser(): BelongsTo { return $this->belongsTo(RoomUser::class); }
}
