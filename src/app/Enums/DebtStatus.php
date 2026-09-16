<?php

declare(strict_types=1);

namespace App\Enums;

enum DebtStatus: string
{
    case Unpaid = 'unpaid';
    case Pending = 'pending';
    case Partial = 'partial';
    case Paid = 'paid';
    case Waived = 'waived';

    /**
     * Return debt statuses that still have an outstanding balance.
     *
     * @return array<string> Outstanding debt status values.
     */
    public static function outstandingValues(): array
    {
        return [self::Unpaid->value, self::Partial->value, self::Pending->value];
    }
}
