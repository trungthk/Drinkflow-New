<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonImmutable;

enum AnalyticsPeriod: string
{
    case Week = 'week';
    case Month = 'month';
    case Quarter = 'quarter';
    case Year = 'year';

    /**
     * Resolve the calendar period as a half-open interval in application time.
     *
     * @param CarbonImmutable $now Current date in the application timezone.
     * @return array{start: CarbonImmutable, end: CarbonImmutable} Inclusive start and exclusive end.
     */
    public function bounds(CarbonImmutable $now): array
    {
        $start = match ($this) {
            self::Week => $now->startOfWeek(),
            self::Month => $now->startOfMonth(),
            self::Quarter => $now->startOfQuarter(),
            self::Year => $now->startOfYear(),
        };

        $end = match ($this) {
            self::Week => $start->addWeek(),
            self::Month => $start->addMonth(),
            self::Quarter => $start->addQuarter(),
            self::Year => $start->addYear(),
        };

        return ['start' => $start, 'end' => $end];
    }
}
