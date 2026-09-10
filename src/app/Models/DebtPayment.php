<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebtPayment extends Model
{
    protected $fillable = ['debt_id', 'amount', 'payment_method', 'reference', 'paid_at', 'created_by_admin_id'];
    protected function casts(): array
    {
        return ['paid_at' => 'datetime'];
    }
}
