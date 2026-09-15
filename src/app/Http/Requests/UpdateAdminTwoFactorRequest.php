<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminTwoFactorRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    public function authorize(): bool
    {
        return $this->authorizeActiveAdmin();
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'two_factor_enabled' => ['required', 'boolean'],
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
            'current_password.required' => __('validation.required', [
                'attribute' => __('validation.attributes.current_password'),
            ]),
            'two_factor_enabled.required' => __('validation.required', [
                'attribute' => __('validation.attributes.two_factor_enabled'),
            ]),
            'two_factor_enabled.boolean' => __('validation.boolean', [
                'attribute' => __('validation.attributes.two_factor_enabled'),
            ]),
        ];
    }
}
