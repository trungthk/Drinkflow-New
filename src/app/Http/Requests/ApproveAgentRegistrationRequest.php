<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PackageStatus;
use App\Enums\SuperadminStatus;
use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Approval of a pending Agent: optional package override and managing Superadmin.
 */
class ApproveAgentRegistrationRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True for an active superadmin (the route checks `agent.approve`).
     */
    public function authorize(): bool
    {
        return $this->authorizeActiveSuperadmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'package_id' => ['nullable', 'integer', Rule::exists('packages', 'id')->where('status', PackageStatus::Active->value)],
            'manager_superadmin_id' => ['nullable', 'integer', Rule::exists('superadmins', 'id')->where('status', SuperadminStatus::Active->value)],
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
            'package_id' => __('platform.registration.field_package'),
            'manager_superadmin_id' => __('platform.registrations.manager'),
        ];
    }
}
