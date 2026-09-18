<?php

declare(strict_types=1);

namespace App\Enums;

enum BillSplitMethod: string
{
    case ByOrder = 'by_order';
    case SponsorFirst = 'sponsor_first';
    case Equal = 'equal';
    case FlatPrice = 'flat_price';
    case Custom = 'custom';
}
