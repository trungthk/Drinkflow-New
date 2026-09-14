<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Version extends Model
{
    public const CACHE_KEY = 'app_latest_system_version';

    protected $fillable = [
        'version',
        'title',
        'changelog',
        'release_date',
        'force_refresh',
        'important',
        'created_by_admin_id',
    ];

    /**
     * Tự động ép kiểu các thuộc tính của model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'release_date' => 'date',
            'force_refresh' => 'boolean',
            'important' => 'boolean',
        ];
    }

    /**
     * Bootstrap model events để tự động làm mới cache khi tạo, cập nhật hoặc xóa version.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            static::clearLatestVersionCache();
        });

        static::deleted(function () {
            static::clearLatestVersionCache();
        });
    }

    /**
     * Lấy chuỗi phiên bản mới nhất từ database kèm cache.
     *
     * @return string  Phiên bản hệ thống (ví dụ: 'v2.3.0')
     */
    public static function getLatestVersionString(): string
    {
        return (string) Cache::rememberForever(self::CACHE_KEY, function () {
            try {
                if (!\Illuminate\Support\Facades\Schema::hasTable('versions')) {
                    return (string) config('app.version', 'v2.3.0');
                }

                $latest = static::query()
                    ->orderByDesc('release_date')
                    ->orderByDesc('id')
                    ->value('version');

                return $latest ?: (string) config('app.version', 'v2.3.0');
            } catch (\Throwable) {
                return (string) config('app.version', 'v2.3.0');
            }
        });
    }

    /**
     * Xóa cache phiên bản hệ thống để làm mới ngay lập tức.
     */
    public static function clearLatestVersionCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
