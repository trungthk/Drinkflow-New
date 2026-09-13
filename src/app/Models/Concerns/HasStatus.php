<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\RoomStatus;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait cung cấp các phương thức và scope quản lý trạng thái (status)
 * cho các Eloquent Models trong hệ thống DrinkFlow.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasStatus
{
    /**
     * Lấy giá trị chuỗi (string) của thuộc tính status, tự động xử lý BackedEnum nếu có.
     *
     * @return string Giá trị trạng thái dạng chuỗi.
     */
    public function getStatusValue(): string
    {
        $status = $this->getAttribute('status');

        if ($status instanceof BackedEnum) {
            return (string) $status->value;
        }

        return (string) ($status ?? '');
    }

    /**
     * Kiểm tra đối tượng có đang ở trạng thái kích hoạt ('active') hay không.
     *
     * @return bool True nếu trạng thái là 'active', ngược lại false.
     */
    public function isActive(): bool
    {
        return $this->getStatusValue() === RoomStatus::Active->value;
    }

    /**
     * Kiểm tra đối tượng có trùng khớp với một trạng thái cụ thể hay không.
     *
     * @param  \BackedEnum|string  $status  Trạng thái cần so khớp.
     * @return bool True nếu trùng khớp trạng thái, ngược lại false.
     */
    public function isStatus(BackedEnum|string $status): bool
    {
        $target = $status instanceof BackedEnum ? (string) $status->value : $status;

        return $this->getStatusValue() === $target;
    }

    /**
     * Scope truy vấn lọc các bản ghi có trạng thái kích hoạt ('active').
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Query Builder instance.
     * @return \Illuminate\Database\Eloquent\Builder  Query Builder đã lọc.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('status'), RoomStatus::Active->value);
    }

    /**
     * Scope truy vấn lọc các bản ghi theo một trạng thái cụ thể.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Query Builder instance.
     * @param  \BackedEnum|string  $status  Trạng thái cần lọc.
     * @return \Illuminate\Database\Eloquent\Builder  Query Builder đã lọc.
     */
    public function scopeStatus(Builder $query, BackedEnum|string $status): Builder
    {
        $val = $status instanceof BackedEnum ? $status->value : $status;

        return $query->where($this->qualifyColumn('status'), $val);
    }

    /**
     * Scope truy vấn lọc các bản ghi có trạng thái nằm trong danh sách chỉ định.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Query Builder instance.
     * @param  array<\BackedEnum|string>  $statuses  Danh sách trạng thái cần lọc.
     * @return \Illuminate\Database\Eloquent\Builder  Query Builder đã lọc.
     */
    public function scopeWhereStatusIn(Builder $query, array $statuses): Builder
    {
        $normalized = array_map(
            static fn (BackedEnum|string $s): string => $s instanceof BackedEnum ? (string) $s->value : (string) $s,
            $statuses
        );

        return $query->whereIn($this->qualifyColumn('status'), $normalized);
    }
}
