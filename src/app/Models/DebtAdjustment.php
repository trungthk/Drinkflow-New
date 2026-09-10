<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebtAdjustment extends Model
{
    protected $fillable = ['debt_id', 'admin_id', 'type', 'amount', 'reason', 'before_amount', 'after_amount'];
}
