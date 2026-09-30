<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PackageStatus;
use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Package change of an Agent subscription (by the Agent or by a Superadmin).
 */
class ChangeSubscriptionPackageRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True for an active admin on /admin, or an active superadmin (the route checks `subscription.manage`).
     */
    public function authorize(): bool
    {
        return $this->is('superadmin/*') ? $this->authorizeActiveSuperadmin() : $this->authorizeActiveAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
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
        return ['package_id' => __('platform.registration.field_package')];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['package_id.exists' => __('platform.subscriptions.package_unavailable')];
    }
}
