<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PackageStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Public Agent (Admin) registration form at /admin/register.
 */
class RegisterAdminRequest extends FormRequest
{
    /**
     * Anyone may register; spam is limited by the captcha and the route throttle.
     *
     * @return bool Always true.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize the email and trim free-text fields before validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'phone' => trim((string) $this->input('phone')),
            'company' => trim((string) $this->input('company')) ?: null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('admins', 'email')],
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9][0-9 .\-]{6,28}$/'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'package_id' => ['required', 'integer', Rule::exists('packages', 'id')->where('status', PackageStatus::Active->value)],
        ];

        $rules['captcha'] = app()->isLocal() || config('captcha.disable')
            ? ['nullable', 'string', 'max:20']
            : ['required', 'string', 'captcha'];

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('platform.registration.field_name'),
            'company' => __('platform.registration.field_company'),
            'email' => __('validation.attributes.email'),
            'phone' => __('platform.registration.field_phone'),
            'password' => __('validation.attributes.password'),
            'package_id' => __('platform.registration.field_package'),
            'captcha' => __('validation.attributes.captcha'),
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => __('platform.registration.email_taken'),
            'phone.regex' => __('platform.registration.phone_format'),
            'package_id.exists' => __('platform.registration.package_unavailable'),
        ];
    }
}
