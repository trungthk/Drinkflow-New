<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Status of a platform invoice sent to an Agent.
 *
 * `open` waits for payment until its due date, then becomes `overdue`; both are outstanding.
 * `paid` is fully settled and `void` was cancelled by a Superadmin (not owed anymore).
 */
enum InvoiceStatus: string
{
    case Open = 'open';
    case Overdue = 'overdue';
    case Paid = 'paid';
    case Void = 'void';

    /**
     * Statuses still owed by the Agent.
     *
     * @return array<int, string> Status values.
     */
    public static function outstandingValues(): array
    {
        return [self::Open->value, self::Overdue->value];
    }

    /**
     * Whether a payment can still be recorded on an invoice in this status.
     *
     * @return bool True while outstanding.
     */
    public function isOutstanding(): bool
    {
        return in_array($this->value, self::outstandingValues(), true);
    }
}
