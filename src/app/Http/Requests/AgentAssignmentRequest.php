<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;
use App\Enums\SuperadminStatus;
use Illuminate\Validation\Rule;

/**
 * Assignment of an Agent to a managing Superadmin.
 */
class AgentAssignmentRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True for an active superadmin (the route checks `agent.manage`, the controller the Agent scope).
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
            'superadmin_id' => ['required', 'integer', Rule::exists('superadmins', 'id')->where('status', SuperadminStatus::Active->value)],
            'is_primary' => ['sometimes', 'boolean'],
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
            'superadmin_id' => __('platform.registrations.manager'),
            'is_primary' => __('platform.agents.primary'),
        ];
    }
}
