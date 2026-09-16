<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebtPayment extends Model
{
    protected $fillable = ['debt_id', 'amount', 'payment_method', 'reference', 'paid_at', 'created_by_admin_id'];
    protected function casts(): array
    {
        return ['paid_at' => 'datetime'];
    }

    /**
     * Get the debt settled by this payment.
     *
     * @return BelongsTo<Debt, $this> Parent debt relation.
     */
    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    /**
     * Get the administrator who recorded or approved the payment.
     *
     * @return BelongsTo<AdminAccount, $this> Administrator relation.
     */
    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(AdminAccount::class, 'created_by_admin_id');
    }
}
