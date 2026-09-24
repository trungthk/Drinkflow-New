<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use App\Services\System\SystemConfigService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Mail transport overrides saved from Superadmin > System settings.
 * Every field is optional: an empty field falls back to the .env value.
 */
class UpdateMailSettingsRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

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
            'mailer' => ['nullable', 'string', Rule::in(SystemConfigService::MAILERS)],
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'between:1,65535'],
            'scheme' => ['nullable', 'string', Rule::in(SystemConfigService::SMTP_SCHEMES)],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'clear_password' => ['sometimes', 'boolean'],
            'from_address' => ['nullable', 'string', 'email:rfc', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
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
            'mailer' => __('superadmin.system.mail_config_mailer'),
            'host' => __('superadmin.system.mail_config_host'),
            'port' => __('superadmin.system.mail_config_port'),
            'scheme' => __('superadmin.system.mail_config_scheme'),
            'username' => __('superadmin.system.mail_config_username'),
            'password' => __('superadmin.system.mail_config_password'),
            'from_address' => __('superadmin.system.mail_config_from_address'),
            'from_name' => __('superadmin.system.mail_config_from_name'),
        ];
    }
}
