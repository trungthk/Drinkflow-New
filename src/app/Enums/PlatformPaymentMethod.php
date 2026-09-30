<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How an Agent paid a platform invoice (recorded manually by a Superadmin).
 */
enum PlatformPaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case Cash = 'cash';
    case Card = 'card';
    // Paid on the provider's hosted checkout page, confirmed by a signed notification.
    case Online = 'online';
    case Other = 'other';
}
