<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PlatformPaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Payment of an Agent against a platform invoice, recorded by a Superadmin.
 *
 * @property int $id
 * @property int $admin_invoice_id
 * @property int $admin_id
 * @property int $amount
 * @property PlatformPaymentMethod $method
 * @property string|null $reference
 * @property \Illuminate\Support\Carbon $paid_at
 * @property int|null $recorded_by_superadmin_id
 * @property string|null $note
 */
class AdminPayment extends Model
{
    protected $fillable = ['admin_invoice_id', 'admin_id', 'amount', 'method', 'reference', 'paid_at', 'recorded_by_superadmin_id', 'note'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'method' => PlatformPaymentMethod::class,
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Paid invoice.
     *
     * @return BelongsTo<AdminInvoice, $this> Invoice.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(AdminInvoice::class, 'admin_invoice_id');
    }

    /**
     * Paying Agent.
     *
     * @return BelongsTo<Admin, $this> Agent.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Superadmin who recorded the payment.
     *
     * @return BelongsTo<Superadmin, $this> Recorder.
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(Superadmin::class, 'recorded_by_superadmin_id');
    }
}
