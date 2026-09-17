<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedbacks';

    protected $fillable = [
        'global_user_id',
        'rating',
        'subsystem',
        'content',
        'user_display_name',
        'department_name',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function globalUser(): BelongsTo
    {
        return $this->belongsTo(GlobalUser::class, 'global_user_id');
    }

    /**
     * Map subsystem code to human-readable label.
     */
    public function getSubsystemLabelAttribute(): string
    {
        return match ($this->subsystem) {
            'room' => __('global.feedback.subsystem_room'),
            'split_qr' => __('global.feedback.subsystem_split_qr'),
            'socket' => __('global.feedback.subsystem_socket'),
            'sponsor' => __('global.feedback.subsystem_sponsor'),
            default => __('global.feedback.subsystem_all'),
        };
    }
}
