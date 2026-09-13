<?php

namespace App\Services\System;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class SystemSettingsService
{
    /**
     * Handle the get operation.
     * @param string $key Parameter value.
     * @param mixed $default Parameter value.
     * @return mixed Result of the operation.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        try {
            if (!Schema::hasTable('system_settings')) return $default;
        } catch (\Throwable $e) {
            return $default;
        }
        $setting = SystemSetting::where('key', $key)->first();
        if (!$setting) return $default;
        $value = $setting->value;
        if ($setting->is_secret && $value !== null) $value = Crypt::decryptString($value);
        return match ($setting->type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int)$value,
            'json' => json_decode($value, true),
            'string' => (string)$value,
            default => $value
        };
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
    public function set(string $key, mixed $value, string $type = 'string', bool $secret = false, ?int $adminId = null): SystemSetting
    {
        $stored = $type === 'json' ? json_encode($value, JSON_THROW_ON_ERROR) : ((string)$value);
        if ($secret) $stored = Crypt::encryptString($stored);
        return SystemSetting::updateOrCreate(['key' => $key], ['value' => $stored, 'type' => $type, 'is_secret' => $secret, 'updated_by_admin_id' => $adminId]);
    }
}
