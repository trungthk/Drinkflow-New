<?php

declare(strict_types=1);

namespace App\Support\Helpers;

use Carbon\Carbon;
use DateTimeInterface;

class FormatHelper
{
    /**
     * Định dạng số tiền theo loại tiền tệ của ứng dụng.
     *
     * @param int|float $amount Số tiền cần định dạng.
     * @param string|null $currency Mã tiền tệ, mặc định lấy từ cấu hình ứng dụng.
     * @param string $suffixSeparator Khoảng cách giữa số tiền và hậu tố tiền tệ.
     * @return string Chuỗi tiền tệ đã định dạng theo locale hiện tại.
     */
    public static function formatCurrency(int|float $amount, ?string $currency = null, string $suffixSeparator = ''): string
    {
        $currencyCode = strtoupper($currency ?? (string) config('app.currency', 'VND'));

        return match ($currencyCode) {
            'USD' => '$' . number_format((float) $amount, 2, '.', ','),
            'JPY' => '¥' . number_format((float) $amount, 0, '.', ','),
            default => number_format((float) $amount, 0, ',', '.') . $suffixSeparator . __('global.common.money_suffix'),
        };
    }

    /**
     * Định dạng ngày theo cấu hình hệ thống (mặc định config('app.date_format', 'd/m/Y')).
     *
     * @param  \DateTimeInterface|string|null  $date  Đối tượng ngày giờ hoặc chuỗi thời gian
     * @param  string|null  $format  Format tùy chọn ghi đè cấu hình
     * @return string  Chuỗi ngày đã định dạng, hoặc rỗng nếu ngày không hợp lệ
     */
    public static function formatDate(DateTimeInterface|string|null $date, ?string $format = null): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        $carbon = is_string($date) ? Carbon::parse($date) : Carbon::instance($date);
        $targetFormat = $format ?? (string) config('app.date_format', 'd/m/Y');

        return $carbon->format($targetFormat);
    }

    /**
     * Định dạng ngày giờ theo cấu hình hệ thống (mặc định config('app.datetime_format', 'd/m/Y H:i:s')).
     *
     * @param  \DateTimeInterface|string|null  $date  Đối tượng ngày giờ hoặc chuỗi thời gian
     * @param  string|null  $format  Format tùy chọn ghi đè cấu hình
     * @return string  Chuỗi ngày giờ đã định dạng, hoặc rỗng nếu ngày không hợp lệ
     */
    public static function formatDateTime(DateTimeInterface|string|null $date, ?string $format = null): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        $carbon = is_string($date) ? Carbon::parse($date) : Carbon::instance($date);
        $targetFormat = $format ?? (string) config('app.datetime_format', 'd/m/Y H:i:s');

        return $carbon->format($targetFormat);
    }

    /**
     * Lấy định dạng ngày mặc định của ứng dụng.
     *
     * @return string  Chuỗi format ngày (ví dụ: 'd/m/Y')
     */
    public static function getDateFormat(): string
    {
        return (string) config('app.date_format', 'd/m/Y');
    }

    /**
     * Lấy định dạng ngày giờ mặc định của ứng dụng.
     *
     * @return string  Chuỗi format ngày giờ (ví dụ: 'd/m/Y H:i:s')
     */
    public static function getDateTimeFormat(): string
    {
        return (string) config('app.datetime_format', 'd/m/Y H:i:s');
    }

    /**
     * Mask chuỗi ký tự (như số tài khoản, mã bảo mật, token...), chỉ giữ lại các ký tự cuối cùng hiển thị.
     *
     * @param string $value Chuỗi cần che giấu.
     * @param int $visibleCount Số ký tự cuối muốn giữ lại hiển thị (mặc định: 4).
     * @param string $maskChar Ký tự dùng để che giấu (mặc định: '•').
     * @return string Chuỗi đã được mask (ví dụ: "••••4382").
     */
    public static function mask(string $value, int $visibleCount = 4, string $maskChar = '•'): string
    {
        $length = mb_strlen($value);
        if ($length <= $visibleCount) {
            return str_repeat($maskChar, $length);
        }

        $maskedPart = str_repeat($maskChar, max(0, $length - $visibleCount));
        $visiblePart = mb_substr($value, -$visibleCount);

        return $maskedPart . $visiblePart;
    }
}
