<?php

declare(strict_types=1);

namespace App\Services\Billing\Gateway;

/**
 * Verified payment notification from the payment provider.
 */
final class GatewayNotification
{
    public const STATUS_PAID = 'paid';

    /**
     * @param string $transactionId Provider transaction ID (idempotency key).
     * @param string $invoiceNumber Paid invoice number.
     * @param int $amount Amount paid in VND.
     * @param string $status Provider status (only `paid` records a payment).
     */
    public function __construct(
        public readonly string $transactionId,
        public readonly string $invoiceNumber,
        public readonly int $amount,
        public readonly string $status,
    ) {
    }

    /**
     * Whether the notification confirms a successful payment.
     *
     * @return bool True for a paid status.
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }
}
