<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Platform invoice of one Agent subscription period (platform finance, not room debt).
 *
 * @property int $id
 * @property string|null $number
 * @property int $admin_id
 * @property int $admin_subscription_id
 * @property int|null $package_id
 * @property \Illuminate\Support\Carbon $period_start
 * @property \Illuminate\Support\Carbon $period_end
 * @property int $subtotal
 * @property int $credit
 * @property int $total
 * @property int $paid_amount
 * @property InvoiceType $type
 * @property InvoiceStatus $status
 * @property \Illuminate\Support\Carbon $issued_at
 * @property \Illuminate\Support\Carbon $due_at
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property \Illuminate\Support\Carbon|null $overdue_at
 * @property \Illuminate\Support\Carbon|null $voided_at
 * @property string|null $void_reason
 */
class AdminInvoice extends Model
{
    protected $fillable = [
        'number', 'admin_id', 'admin_subscription_id', 'type', 'package_id', 'period_start', 'period_end', 'subtotal', 'credit', 'total',
        'paid_amount', 'status', 'issued_at', 'due_at', 'paid_at', 'overdue_at', 'voided_at', 'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'type' => InvoiceType::class,
            'subtotal' => 'integer',
            'credit' => 'integer',
            'total' => 'integer',
            'paid_amount' => 'integer',
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'issued_at' => 'datetime',
            'due_at' => 'datetime',
            'paid_at' => 'datetime',
            'overdue_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    /**
     * Amount still owed.
     *
     * @return int Remaining amount in VND (0 once paid or void).
     */
    public function remaining(): int
    {
        return $this->status->isOutstanding() ? max(0, $this->total - $this->paid_amount) : 0;
    }

    /**
     * Whether this invoice pays for a package upgrade (applied once it is fully paid).
     *
     * @return bool True for an upgrade invoice.
     */
    public function isUpgrade(): bool
    {
        return $this->type === InvoiceType::Upgrade;
    }

    /**
     * Invoices still owed (open or overdue).
     *
     * @param Builder<AdminInvoice> $query Invoice query.
     * @return Builder<AdminInvoice> Outstanding invoices.
     */
    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereIn('status', InvoiceStatus::outstandingValues());
    }

    /**
     * Billed Agent.
     *
     * @return BelongsTo<Admin, $this> Agent.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Billed subscription.
     *
     * @return BelongsTo<AdminSubscription, $this> Subscription.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(AdminSubscription::class, 'admin_subscription_id');
    }

    /**
     * Package of the billed subscription.
     *
     * @return BelongsTo<Package, $this> Package.
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Payments recorded on this invoice.
     *
     * @return HasMany<AdminPayment, $this> Payments.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(AdminPayment::class);
    }
}
