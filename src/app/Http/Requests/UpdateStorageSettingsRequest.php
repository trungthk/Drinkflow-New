<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use App\Services\System\SystemConfigService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Storage overrides saved from Superadmin > System settings.
 * Every field is optional: an empty field falls back to the .env value.
 */
class UpdateStorageSettingsRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /** Upper bound for the storage quota (100 TB in MB) to reject typos. */
    public const QUOTA_MAX_MB = 104857600;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True when the caller is an active superadmin.
     */
    public function authorize(): bool
    {
        return $this->authorizeActiveSuperadmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'disk' => ['nullable', 'string', Rule::in(app(SystemConfigService::class)->localDisks())],
            'quota_mb' => ['nullable', 'integer', 'between:1,'.self::QUOTA_MAX_MB],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'disk' => __('superadmin.system.storage_config_disk'),
            'quota_mb' => __('superadmin.system.storage_config_quota'),
        ];
    }
}
