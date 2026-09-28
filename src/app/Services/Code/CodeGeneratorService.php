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
     * Generate a unique debt code formatted as <yymmdd><roomId><XXXX>, e.g. 2609281XIAU.
     *
     * Existing debts keep the codes they were created with (legacy DEB-YYYYMMDD-XXXX).
     *
     * @param int|null $roomId Room identifier embedded in the code (omitted when unknown).
     * @param CarbonInterface|null $date Date embedded in the code (defaults to today).
     * @return string Unique debt code.
     */
    public static function generateDebtCode(?int $roomId = null, ?CarbonInterface $date = null): string
    {
        $prefix = ($date ?? now())->format('ymd') . ($roomId ?? '');
        do {
            $code = $prefix . self::randomAlphanumeric(4);
        } while (DB::table('debts')->where('code', $code)->exists());

        return $code;
    }

    /**
     * Generate a random string of uppercase letters and digits (A-Z0-9).
     *
     * @param int $length Number of characters.
     * @return string Random string.
     */
    private static function randomAlphanumeric(int $length): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $result;
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
