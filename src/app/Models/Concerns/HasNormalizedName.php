<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Trait tự động chuẩn hóa và tìm kiếm theo tên không dấu / chữ in hoa cho Model.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasNormalizedName
{
    /**
     * Tự động khởi tạo event listeners cho việc chuẩn hóa tên.
     */
    public static function bootHasNormalizedName(): void
    {
        static::saving(function (Model $model): void {
            $sourceField = $model->getAttribute('name') !== null ? 'name' : ($model->getAttribute('display_name') !== null ? 'display_name' : null);

            if ($sourceField && empty($model->getAttribute('normalized_name'))) {
                $model->setAttribute('normalized_name', static::normalizeString((string) $model->getAttribute($sourceField)));
            }
        });
    }

    /**
     * Chuẩn hóa chuỗi văn bản loại bỏ dấu tiếng Việt và chuyển sang chữ IN HOA.
     *
     * @param  string  $value  Chuỗi ký tự cần chuẩn hóa.
     * @return string Chuỗi đã chuẩn hóa.
     */
    public static function normalizeString(string $value): string
    {
        $ascii = Str::ascii($value);

        return strtoupper(trim((string) preg_replace('/\s+/', ' ', $ascii)));
    }

    /**
     * Scope tìm kiếm bản ghi theo tên hoặc tên chuẩn hóa không dấu.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Query Builder.
     * @param  string  $term  Từ khóa tìm kiếm.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchNormalizedName(Builder $query, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        $normalizedTerm = static::normalizeString($term);

        return $query->where(function (Builder $q) use ($term, $normalizedTerm): void {
            $q->where($this->qualifyColumn('name'), 'like', "%{$term}%")
              ->orWhere($this->qualifyColumn('normalized_name'), 'like', "%{$normalizedTerm}%");
        });
    }
}
