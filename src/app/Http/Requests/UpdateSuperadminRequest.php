<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\SuperadminStatus;
use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Superadmin profile, password (optional) and status update.
 */
class UpdateSuperadminRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True for an active superadmin (the route checks `superadmin.manage`).
     */
    public function authorize(): bool
    {
        return $this->authorizeActiveSuperadmin();
    }

    /**
     * Normalize the email and treat an empty password as "unchanged".
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'password' => $this->filled('password') ? $this->input('password') : null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $superadmin = $this->route('superadmin');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('superadmins', 'email')->ignore($superadmin)],
            'password' => ['nullable', 'string', 'min:8', 'max:255', 'confirmed'],
            'status' => ['required', Rule::enum(SuperadminStatus::class)],
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
            'name' => __('validation.attributes.name'),
            'email' => __('validation.attributes.email'),
            'password' => __('validation.attributes.password'),
            'status' => __('validation.attributes.status'),
        ];
    }
}
