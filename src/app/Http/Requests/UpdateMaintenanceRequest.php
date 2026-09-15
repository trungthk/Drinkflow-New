<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMaintenanceRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    public function authorize(): bool
    {
        return $this->authorizeActiveSuperadmin();
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
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
            'enabled.required' => __('validation.required', [
                'attribute' => __('validation.attributes.enabled'),
            ]),
            'enabled.boolean' => __('validation.boolean', [
                'attribute' => __('validation.attributes.enabled'),
            ]),
            'ends_at.after_or_equal' => __('validation.after_or_equal', [
                'attribute' => __('validation.attributes.ends_at'),
                'date' => __('validation.attributes.starts_at'),
            ]),
        ];
    }
}
