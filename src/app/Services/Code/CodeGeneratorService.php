<?php

declare(strict_types=1);

namespace App\Services\Code;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CodeGeneratorService
{
    /**
     * Generate the daily sequence code for an order in a room.
     *
     * @param int|null $roomId Room identifier used in the code.
     * @param CarbonInterface|null $date Date used for the daily sequence.
     * @return string Unique order code.
     */
    public static function generateOrderCode(?int $roomId = null, ?CarbonInterface $date = null): string
    {
        // Keep the no-argument form available for legacy migration/test callers.
        if ($roomId === null) {
            $prefix = 'ORD-' . ($date?->format('Ymd') ?? date('Ymd')) . '-';
            do {
                $code = $prefix . strtoupper(Str::random(4));
            } while (DB::table('orders')->where('code', $code)->exists());

            return $code;
        }

        $date ??= now();
        $dateKey = $date->format('Ymd');
        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        // The order creation action locks the room row before inserting. Lock it
        // here as well for callers that create orders inside a transaction.
        if (DB::transactionLevel() > 0) {
            DB::table('rooms')->where('id', $roomId)->lockForUpdate()->first();
        }

        $codes = DB::table('orders')
            ->where('room_id', $roomId)
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->pluck('code');
        $pattern = '/^ORD' . preg_quote((string) $roomId, '/') . '-' . preg_quote($dateKey, '/') . '-(\d{3})$/';
        $sequence = 0;
        foreach ($codes as $existingCode) {
            if (is_string($existingCode) && preg_match($pattern, $existingCode, $matches) === 1) {
                $sequence = max($sequence, (int) $matches[1]);
            }
        }

        $sequence++;
        if ($sequence > 999) {
            throw new \RuntimeException('The daily order sequence has reached its 999-order limit.');
        }

        return sprintf('ORD%d-%s-%03d', $roomId, $dateKey, $sequence);
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

    /**
     * Generate a unique global user code as UUID string.
     *
     * @return string Unique UUID string.
     */
    public static function generateGlobalUserCode(): string
    {
        do {
            $code = (string) Str::uuid();
        } while (DB::table('global_users')->where('code', $code)->exists());

        return $code;
    }

    /**
     * Generate a unique room user code as UUID string.
     *
     * @return string Unique UUID string.
     */
    public static function generateRoomUserCode(): string
    {
        do {
            $code = (string) Str::uuid();
        } while (DB::table('room_users')->where('user_code', $code)->exists());

        return $code;
    }
}
