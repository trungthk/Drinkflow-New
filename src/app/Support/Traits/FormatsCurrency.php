<?php

declare(strict_types=1);

namespace App\Support\Traits;

use App\Support\Helpers\FormatHelper;

/**
 * Trait cung cấp các hàm helper định dạng tiền tệ và tính toán chiết khấu/chia bill.
 */
trait FormatsCurrency
{
    /**
     * Định dạng số tiền sang định dạng tiền Việt Nam Đồng (VND).
     *
     * @param  int|float  $amount  Số tiền.
     * @return string Chuỗi tiền tệ đã định dạng (ví dụ: "50.000 đ").
     */
    public function formatVnd(int|float $amount): string
    {
        return FormatHelper::formatCurrency($amount, 'VND', ' ');
    }

    /**
     * Định dạng số tiền kèm mã tiền tệ tùy chỉnh (VND, USD, JPY).
     *
     * @param  int|float  $amount  Số tiền.
     * @param  string  $currency  Đơn vị tiền tệ (mặc định 'VND').
     * @return string Chuỗi tiền tệ đã định dạng.
     */
    public function formatCurrency(int|float $amount, string $currency = 'VND'): string
    {
        return FormatHelper::formatCurrency($amount, $currency);
    }

    /**
     * Tính toán chia đều số tiền cho danh sách người tham gia.
     *
     * @param  int  $totalAmount  Tổng số tiền cần chia.
     * @param  int  $participantCount  Số lượng người chia.
     * @return array<int, int> Mảng số tiền từng người phải trả (đã xử lý phần dư làm tròn).
     */
    public function splitEqually(int $totalAmount, int $participantCount): array
    {
        if ($participantCount <= 0) {
            return [];
        }

        $base = intdiv($totalAmount, $participantCount);
        $remainder = $totalAmount % $participantCount;

        $result = array_fill(0, $participantCount, $base);

        for ($i = 0; $i < $remainder; $i++) {
            $result[$i]++;
        }

        return $result;
    }
}
