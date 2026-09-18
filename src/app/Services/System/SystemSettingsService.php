<?php

declare(strict_types=1);

namespace App\Services\System;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Crypt;

class SystemSettingsService
{
    private static array $cache = [];

    /**
     * Handle the get operation.
     * @param string $key Parameter value.
     * @param mixed $default Parameter value.
     * @return mixed Result of the operation.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        try {
            $setting = SystemSetting::where('key', $key)->first();
            if (!$setting) {
                return self::$cache[$key] = $default;
            }
            $value = $setting->value;
            if ($setting->is_secret && $value !== null) {
                $value = Crypt::decryptString($value);
            }
            $result = match ($setting->type) {
                SystemSetting::TYPE_BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                SystemSetting::TYPE_INTEGER => (int) $value,
                SystemSetting::TYPE_JSON => json_decode($value, true),
                SystemSetting::TYPE_STRING => (string) $value,
                default => $value
            };
            return self::$cache[$key] = $result;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Handle the set operation.
     * @param string $key Parameter value.
     * @param mixed $value Parameter value.
     * @param string $type Parameter value.
     * @param bool $secret Parameter value.
     * @param ?int $adminId Parameter value.
     * @return SystemSetting Result of the operation.
     */
    public function set(string $key, mixed $value, string $type = SystemSetting::TYPE_STRING, bool $secret = false, ?int $adminId = null): SystemSetting
    {
        unset(self::$cache[$key]);
        $stored = $type === SystemSetting::TYPE_JSON ? json_encode($value, JSON_THROW_ON_ERROR) : ((string) $value);
        if ($secret) $stored = Crypt::encryptString($stored);
        return SystemSetting::updateOrCreate(['key' => $key], ['value' => $stored, 'type' => $type, 'is_secret' => $secret, 'updated_by_admin_id' => $adminId]);
    }

    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
