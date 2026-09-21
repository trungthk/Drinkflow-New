<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Transfer = 'transfer';
    case Qr = 'qr';
    case VietQr = 'vietqr';
    case RoomFund = 'room_fund';
}
