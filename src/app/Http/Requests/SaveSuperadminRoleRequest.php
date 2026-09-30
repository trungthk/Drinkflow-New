<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\SuperadminRole;
use Illuminate\Validation\Rule;

/**
 * Superadmin role form: identity plus the same permission/scope matrix as a Superadmin's grants.
 */
class SaveSuperadminRoleRequest extends SyncSuperadminPermissionsRequest
{
    /**
     * Normalize the code before validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['code' => mb_strtolower(trim((string) $this->input('code')))]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $role = $this->route('role');

        return parent::rules() + [
            'code' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9][a-z0-9_-]*$/', Rule::unique('superadmin_roles', 'code')->ignore($role instanceof SuperadminRole ? $role->id : null)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Role identity fields.
     *
     * @return array{code: string, name: string, description: string|null} Validated identity.
     */
    public function identity(): array
    {
        return [
            'code' => (string) $this->validated('code'),
            'name' => (string) $this->validated('name'),
            'description' => $this->validated('description'),
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
            'code' => __('platform.roles.field_code'),
            'name' => __('platform.roles.field_name'),
            'description' => __('platform.roles.field_description'),
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['code.regex' => __('platform.packages.code_format')];
    }
}
