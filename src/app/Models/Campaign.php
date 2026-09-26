<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CampaignStatus;
use App\Models\Concerns\BelongsToRoom;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasStatus, BelongsToRoom;

    public const SPONSOR_TYPE_NONE = 'none';

    public const SPONSOR_TYPE_FULL = 'full';

    public const SPONSOR_TYPE_PER_ITEM = 'per_item';

    public const SPONSOR_TYPE_BUDGET = 'budget';

    /** Every sponsor type a campaign can have, in the order they are listed in filters. */
    public const SPONSOR_TYPES = [
        self::SPONSOR_TYPE_NONE,
        self::SPONSOR_TYPE_FULL,
        self::SPONSOR_TYPE_PER_ITEM,
        self::SPONSOR_TYPE_BUDGET,
    ];

    /** Nợ trả riêng lấy đúng đơn giá món, không cộng ship và không trừ giảm giá/chiết khấu chung của chiến dịch. */
    public const SELF_PAID_PRICE_BASIS_ORIGINAL = 'original';

    /** Nợ trả riêng được phân bổ theo tỷ lệ ship và giảm giá/chiết khấu chung của chiến dịch, như các món được tài trợ. */
    public const SELF_PAID_PRICE_BASIS_CAMPAIGN_PRORATED = 'campaign_prorated';

    /** Minutes an admin may add to an ordering deadline in one step. */
    public const EXTEND_DEADLINE_MINUTES = [10, 20, 30, 60];

    protected $fillable = ['room_id', 'code', 'name', 'restaurant', 'creator_admin_id', 'sponsor_name', 'sponsor_type', 'sponsor_description', 'sponsor_allocations', 'deadline', 'max_budget', 'flat_price', 'delivery_fee', 'discount', 'self_paid_price_basis', 'payment_account_id', 'description', 'status', 'started_at', 'closed_at'];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::creating(function (Campaign $campaign): void {
            if (empty($campaign->code)) {
                $campaign->code = \App\Services\Code\CodeGeneratorService::generateCampaignCode();
            }
        });
    }

    protected function casts(): array
    {
        return ['deadline' => 'datetime', 'started_at' => 'datetime', 'closed_at' => 'datetime', 'status' => CampaignStatus::class, 'sponsor_allocations' => 'array'];
    }

    /**
     * Determine whether this campaign currently accepts new orders.
     *
     * @return bool True when the campaign is live and its ordering deadline has not passed.
     */
    public function isOrderable(): bool
    {
        return $this->status === CampaignStatus::Active
            && ($this->deadline === null || $this->deadline->isFuture());
    }

    /**
     * Determine whether this campaign is finalized and must no longer be edited.
     *
     * @return bool True when the campaign is closed or archived.
     */
    public function isLocked(): bool
    {
        return in_array($this->status, [CampaignStatus::Closed, CampaignStatus::Archived], true);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CampaignItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function debts(): HasMany
    {
        return $this->hasMany(Debt::class);
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class);
    }

    /**
     * Get the administrator who created the campaign.
     *
     * @return BelongsTo<AdminAccount, $this> Campaign creator relationship.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminAccount::class, 'creator_admin_id');
    }
}
