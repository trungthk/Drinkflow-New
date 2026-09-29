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

    // Decision states of a consolidated payment request (the parent `debts` row); campaign debts never use them.
    case Approved = 'approved';
    case Rejected = 'rejected';

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
