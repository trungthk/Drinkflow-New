<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentAccountStatus: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
}
