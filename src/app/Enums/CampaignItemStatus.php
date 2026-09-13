<?php

declare(strict_types=1);

namespace App\Enums;

enum CampaignItemStatus: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
    case SoldOut  = 'sold_out';
}
