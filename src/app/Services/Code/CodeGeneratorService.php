<?php

declare(strict_types=1);

namespace App\Services\Code;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CodeGeneratorService
{
    /**
     * Generate a unique order code formatted as ORD-YYYYMMDD-XXXX.
     *
     * @return string Unique order code.
     */
    public static function generateOrderCode(): string
    {
        $prefix = 'ORD-' . date('Ymd') . '-';
        do {
            $code = $prefix . strtoupper(Str::random(4));
        } while (DB::table('orders')->where('code', $code)->exists());

        return $code;
    }

    /**
     * Generate a unique campaign code formatted as CMP-YYYYMMDD-XXXX.
     *
     * @return string Unique campaign code.
     */
    public static function generateCampaignCode(): string
    {
        $prefix = 'CMP-' . date('Ymd') . '-';
        do {
            $code = $prefix . strtoupper(Str::random(4));
        } while (DB::table('campaigns')->where('code', $code)->exists());

        return $code;
    }

    /**
     * Generate a unique debt code formatted as DEB-YYYYMMDD-XXXX.
     *
     * @return string Unique debt code.
     */
    public static function generateDebtCode(): string
    {
        $prefix = 'DEB-' . date('Ymd') . '-';
        do {
            $code = $prefix . strtoupper(Str::random(4));
        } while (DB::table('debts')->where('code', $code)->exists());

        return $code;
    }
}
