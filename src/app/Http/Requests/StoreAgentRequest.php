<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PackageStatus;
use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A Superadmin creating a new Agent (Agents → Add Agent).
 *
 * The Agent account is created `pending` and cannot sign in: the invited person chooses its own
 * sign-in value through the activation link, so no credential is ever passed through this form
 * (or through email).
 */
class StoreAgentRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True for an active superadmin (the route checks `agent.manage`).
     */
    public function authorize(): bool
    {
        return $this->authorizeActiveSuperadmin();
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('admins', 'email')],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9][0-9 .\-]{6,28}$/'],
            'package_id' => ['required', 'integer', Rule::exists('packages', 'id')->where('status', PackageStatus::Active->value)],
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
            'name' => __('platform.registration.field_name'),
            'company' => __('platform.registration.field_company'),
            'email' => __('validation.attributes.email'),
            'phone' => __('platform.registration.field_phone'),
            'package_id' => __('platform.registration.field_package'),
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
