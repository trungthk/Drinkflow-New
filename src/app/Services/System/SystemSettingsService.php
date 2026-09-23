<?php

declare(strict_types=1);

namespace App\Services\System;

use App\Models\SystemSetting;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
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

    /**
     * Resolve the maintenance window configured by superadmins.
     *
     * Maintenance is "active" when it is enabled and the current time is inside the optional
     * [starts_at, ends_at] window; an enabled window that has not started yet is "scheduled".
     *
     * @return array{enabled: bool, active: bool, scheduled: bool, starts_at: ?string, ends_at: ?string, starts: ?CarbonInterface, ends: ?CarbonInterface}
     */
    public function maintenanceState(): array
    {
        $enabled = (bool) $this->get('maintenance.enabled', false);
        $startsAt = $this->get('maintenance.starts_at') ?: null;
        $endsAt = $this->get('maintenance.ends_at') ?: null;
        $starts = $this->parseDate($startsAt);
        $ends = $this->parseDate($endsAt);
        $now = now();
        $notStarted = $starts !== null && $now->lt($starts);
        $finished = $ends !== null && $now->gt($ends);

        return [
            'enabled' => $enabled,
            'active' => $enabled && ! $notStarted && ! $finished,
            'scheduled' => $enabled && $notStarted,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'starts' => $starts,
            'ends' => $ends,
        ];
    }

    /**
     * Parse a stored maintenance boundary, ignoring values that are not valid dates.
     *
     * @param mixed $value Stored setting value (datetime-local string or null).
     * @return CarbonInterface|null Parsed date in the application timezone.
     */
    private function parseDate(mixed $value): ?CarbonInterface
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
