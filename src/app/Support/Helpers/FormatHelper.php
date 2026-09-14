<?php

declare(strict_types=1);

namespace App\Support\Helpers;

use Carbon\Carbon;
use DateTimeInterface;

class FormatHelper
{
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
}
