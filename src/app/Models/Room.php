<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoomStatus;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Room extends Model
{
    use HasStatus;

    protected $fillable = ['name', 'slug', 'description', 'avatar_url', 'status', 'timezone', 'language', 'settings'];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'status'   => RoomStatus::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if ($field) {
            return $this->where($field, $value)->first();
        }

        if (is_numeric($value)) {
            return $this->where('id', (int) $value)->orWhere('slug', (string) $value)->first();
        }

        return $this->where('slug', (string) $value)->first();
    }

    public function roomUsers(): HasMany
    {
        return $this->hasMany(RoomUser::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function paymentAccounts(): HasMany
    {
        return $this->hasMany(PaymentAccount::class);
    }

    public function roomSettings(): HasMany
    {
        return $this->hasMany(RoomSetting::class);
    }

    public function notificationChannels(): HasMany
    {
        return $this->hasMany(NotificationChannel::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function debts(): HasMany
    {
        return $this->hasMany(Debt::class);
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(AdminAccount::class, 'admin_rooms', 'room_id', 'admin_id');
    }

    /**
     * Check if the room currently has any member with an outstanding debt balance.
     *
     * @return bool True if outstanding debt exists in room, false otherwise.
     */
    public function hasOutstandingDebts(): bool
    {
        return $this->debts()
            ->whereIn('status', \App\Enums\DebtStatus::outstandingValues())
            ->where('remaining_amount', '>', 0)
            ->exists();
    }

    /**
     * Check if the room currently has a running (active or closing) campaign.
     *
     * @return bool True if a running campaign exists in the room, false otherwise.
     */
    public function hasActiveCampaign(): bool
    {
        return $this->campaigns()
            ->whereIn('status', \App\Enums\CampaignStatus::running())
            ->exists();
    }

    /**
     * Resolve the campaign whose orders the admin currently manages: the newest running campaign,
     * or the most recently closed one when nothing is running. Drafts, scheduled, cancelled and
     * archived campaigns are ignored.
     *
     * @return Campaign|null Latest ordering campaign, or null when the room has none yet.
     */
    public function latestOrderCampaign(): ?Campaign
    {
        return $this->campaigns()->whereIn('status', \App\Enums\CampaignStatus::running())->latest()->latest('id')->first()
            ?? $this->campaigns()->where('status', \App\Enums\CampaignStatus::Closed)->latest()->latest('id')->first();
    }
}
