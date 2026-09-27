<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Who placed an order: the member themselves, a room admin on their behalf, or another member (proxy order).
 */
enum OrderPlacementType: string
{
    case Self = 'self';
    case Admin = 'admin';
    case Proxy = 'proxy';

    /**
     * Get the translated label of this order type.
     *
     * @return string Localized label.
     */
    public function label(): string
    {
        return __('admin.order_type_' . $this->value);
    }
}
