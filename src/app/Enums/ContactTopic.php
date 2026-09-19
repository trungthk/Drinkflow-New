<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactTopic: string
{
    case VietQR = 'vietqr';
    case Deploy = 'deploy';
    case Feedback = 'feedback';
    case Merchant = 'merchant';
    case Other = 'other';

    /**
     * Get the translated label for this contact topic.
     *
     * @return string
     */
    public function label(): string
    {
        return __('contact.form.topics.' . $this->value);
    }
}
