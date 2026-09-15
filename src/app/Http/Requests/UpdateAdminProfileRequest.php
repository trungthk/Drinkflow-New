<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminProfileRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'department' => ['nullable', 'string', 'max:255'],
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
            'name.required' => __('validation.required', [
                'attribute' => __('validation.attributes.name'),
            ]),
            'name.max' => __('validation.max.string', [
                'attribute' => __('validation.attributes.name'),
                'max' => 255,
            ]),
            'phone.max' => __('validation.max.string', [
                'attribute' => __('validation.attributes.phone'),
                'max' => 30,
            ]),
            'department.max' => __('validation.max.string', [
                'attribute' => __('validation.attributes.department'),
                'max' => 255,
            ]),
        ];
    }
}
