<?php

declare(strict_types=1);

namespace App\Enums;

enum DebtAdjustmentType: string
{
    case Increase = 'increase';
    case Decrease = 'decrease';
    case Waive = 'waive';
    case Correction = 'correction';
}
