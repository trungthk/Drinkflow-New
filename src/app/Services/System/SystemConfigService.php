<?php

declare(strict_types=1);

namespace App\Services\System;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Superadmin-managed overrides for the mail transport and file storage.
 *
 * Every field is optional: a value saved from the System settings page wins, an empty field
 * falls back to what config/*.php read from .env. The overrides are applied to the runtime
 * config on every boot (see AppServiceProvider), so all mail/storage code keeps reading config().
 *
 * Registered as a singleton: the .env values are captured once, before any override, so the
 * settings page can show them and a cleared field can be restored without re-reading .env.
 */
class SystemConfigService
{
    public const GROUP_MAIL = 'mail';
    public const GROUP_STORAGE = 'storage';

    /** Mail transports the settings page may select. */
    public const MAILERS = ['smtp', 'sendmail', 'log'];

    /** SMTP schemes accepted by Laravel's mailer ("smtps" = implicit TLS, usually port 465). */
    public const SMTP_SCHEMES = ['smtp', 'smtps'];

    /**
     * Setting key suffix => config path, cast and whether the value is stored encrypted.
     * The stored setting key is "<group>.<field>", e.g. "mail.host".
     */
    private const FIELDS = [
        self::GROUP_MAIL => [
            'mailer' => ['config' => 'mail.default', 'type' => 'string'],
            'host' => ['config' => 'mail.mailers.smtp.host', 'type' => 'string'],
            'port' => ['config' => 'mail.mailers.smtp.port', 'type' => 'integer'],
            'scheme' => ['config' => 'mail.mailers.smtp.scheme', 'type' => 'string'],
            'username' => ['config' => 'mail.mailers.smtp.username', 'type' => 'string'],
            'password' => ['config' => 'mail.mailers.smtp.password', 'type' => 'string', 'secret' => true],
            'from_address' => ['config' => 'mail.from.address', 'type' => 'string'],
            'from_name' => ['config' => 'mail.from.name', 'type' => 'string'],
        ],
        self::GROUP_STORAGE => [
            'disk' => ['config' => 'filesystems.default', 'type' => 'string'],
            'quota_mb' => ['config' => 'filesystems.quota_mb', 'type' => 'integer'],
        ],
    ];

    /** @var array<string, mixed>|null Config values read from .env, keyed by config path. */
    private ?array $envValues = null;

    public function __construct(private readonly SystemSettingsService $settings)
    {
    }

    /**
     * Apply saved overrides on top of the .env-based config for the current process.
     *
     * Silently keeps the .env config when the settings table is not reachable yet
     * (fresh install, migrations pending, test bootstrap before RefreshDatabase).
     */
    public function apply(): void
    {
        $this->captureEnvValues();

        try {
            $saved = $this->settings->many($this->settingKeys());
        } catch (Throwable) {
            return;
        }

        $overrides = [];
        foreach ($this->fieldDefinitions() as $key => $definition) {
            $overrides[$definition['config']] = array_key_exists($key, $saved) && ! $this->isBlank($saved[$key])
                ? $saved[$key]
                : $this->envValues[$definition['config']];
        }
        config($overrides);

        // Mailers resolved earlier in this process would keep the old transport.
        if (app()->resolved('mail.manager')) {
            app('mail.manager')->forgetMailers();
        }
    }

    /**
     * Describe one group's fields for the settings page.
     *
     * Secrets never leave the server: only whether they are configured is reported.
     *
     * @param string $group self::GROUP_MAIL or self::GROUP_STORAGE.
     * @return array<string, array{value: mixed, configured: bool, env_value: mixed, env_configured: bool, source: 'system'|'env', secret: bool}>
     */
    public function describe(string $group): array
    {
        $this->captureEnvValues();
        $saved = $this->settings->many($this->settingKeys($group));

        $fields = [];
        foreach (self::FIELDS[$group] as $field => $definition) {
            $key = $group.'.'.$field;
            $secret = (bool) ($definition['secret'] ?? false);
            $configured = array_key_exists($key, $saved) && ! $this->isBlank($saved[$key]);
            $envValue = $this->envValues[$definition['config']];

            $fields[$field] = [
                'value' => $configured && ! $secret ? $saved[$key] : null,
                'configured' => $configured,
                'env_value' => $secret ? null : $envValue,
                'env_configured' => ! $this->isBlank($envValue),
                'source' => $configured ? 'system' : 'env',
                'secret' => $secret,
            ];
        }

        return $fields;
    }

    /**
     * Save one group's fields: a filled field is stored, an empty one is removed (back to .env).
     *
     * Secret fields are the exception: leaving them empty keeps the stored value, and only
     * `clear_<field>` = true removes it, so the page never has to echo a password back.
     *
     * @param string $group self::GROUP_MAIL or self::GROUP_STORAGE.
     * @param array<string, mixed> $input Validated field values (and optional clear_<field> flags).
     * @param int|null $adminId Superadmin performing the change.
     * @return array<int, string> Names of the fields whose stored value changed (for the audit log).
     */
    public function save(string $group, array $input, ?int $adminId): array
    {
        $saved = $this->settings->many($this->settingKeys($group));
        $changed = [];

        foreach (self::FIELDS[$group] as $field => $definition) {
            $key = $group.'.'.$field;
            $value = $input[$field] ?? null;

            if ($definition['secret'] ?? false) {
                if (! empty($input['clear_'.$field])) {
                    if ($this->settings->forget($key)) {
                        $changed[] = $field;
                    }
                } elseif (! $this->isBlank($value)) {
                    $this->settings->set($key, $value, $definition['type'], true, $adminId);
                    $changed[] = $field;
                }

                continue;
            }

            if ($this->isBlank($value)) {
                if ($this->settings->forget($key)) {
                    $changed[] = $field;
                }

                continue;
            }

            $typed = $definition['type'] === 'integer' ? (int) $value : (string) $value;
            if (! array_key_exists($key, $saved) || $saved[$key] !== $typed) {
                $this->settings->set($key, $typed, $definition['type'], false, $adminId);
                $changed[] = $field;
            }
        }

        $this->apply();
        if ($changed !== []) {
            $this->restartQueueWorkers();
        }

        return $changed;
    }

    /**
     * Local-driver disks the storage settings may select as the default disk.
     *
     * @return array<int, string> Disk names from config/filesystems.php.
     */
    public function localDisks(): array
    {
        return array_keys(array_filter(
            (array) config('filesystems.disks', []),
            static fn (mixed $disk): bool => is_array($disk) && ($disk['driver'] ?? null) === 'local'
        ));
    }

    /**
     * Remember the .env-based config once, before any override touches it.
     */
    private function captureEnvValues(): void
    {
        if ($this->envValues !== null) {
            return;
        }

        $this->envValues = [];
        foreach ($this->fieldDefinitions() as $definition) {
            $this->envValues[$definition['config']] = config($definition['config']);
        }
    }

    /**
     * Ask long-running queue workers to restart so they pick up the new mail/storage config.
     */
    private function restartQueueWorkers(): void
    {
        try {
            Artisan::call('queue:restart');
        } catch (Throwable $e) {
            Log::warning('Could not signal queue workers to restart after a system config change.', ['exception' => $e::class]);
        }
    }

    /**
     * @return array<string, array{config: string, type: string, secret?: bool}> Definitions keyed by setting key.
     */
    private function fieldDefinitions(): array
    {
        $definitions = [];
        foreach (self::FIELDS as $group => $fields) {
            foreach ($fields as $field => $definition) {
                $definitions[$group.'.'.$field] = $definition;
            }
        }

        return $definitions;
    }

    /**
     * @param string|null $group Limit to one group, or every managed key when null.
     * @return array<int, string> Stored setting keys.
     */
    private function settingKeys(?string $group = null): array
    {
        $keys = array_keys($this->fieldDefinitions());

        return $group === null
            ? $keys
            : array_values(array_filter($keys, static fn (string $key): bool => str_starts_with($key, $group.'.')));
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }
}
