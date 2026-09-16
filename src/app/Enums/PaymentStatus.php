<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Pending = 'pending';
    case Paid = 'paid';

    /**
     * Determine whether the payment has been fully confirmed.
     *
     * @return bool True if paid, false otherwise.
     */
    public function isPaid(): bool
    {
        return $this === self::Paid;
    }

    /**
     * Determine whether the payment is waiting for admin confirmation.
     *
     * @return bool True if waiting for approval, false otherwise.
     */
    public function isPending(): bool
    {
        return $this === self::Pending;
    }
}
