<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SystemSettingsRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True nếu là Superadmin đang active.
     */
    public function authorize(): bool
    {
        return $this->authorizeActiveSuperadmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'settings' => ['required', 'array', 'min:1'],
            // Mail/storage keys are managed by their own endpoints (typed, validated, secrets encrypted).
            'settings.*.key' => ['required', 'string', 'max:150', 'not_regex:/^(mail|storage)\./'],
            'settings.*.value' => ['nullable'],
            'settings.*.type' => ['sometimes', Rule::in(['string', 'boolean', 'integer', 'json'])],
            'settings.*.is_secret' => ['sometimes', 'boolean'],
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
            'settings' => __('validation.attributes.settings'),
            'settings.*.key' => __('validation.attributes.key'),
            'settings.*.value' => __('validation.attributes.value'),
            'settings.*.type' => __('validation.attributes.type'),
            'settings.*.is_secret' => __('validation.attributes.is_secret'),
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
            'settings.required' => __('validation.required', ['attribute' => __('validation.attributes.settings')]),
            'settings.array' => __('validation.array', ['attribute' => __('validation.attributes.settings')]),
            'settings.min' => __('validation.min.array', ['attribute' => __('validation.attributes.settings'), 'min' => 1]),
            'settings.*.key.required' => __('validation.required', ['attribute' => __('validation.attributes.key')]),
            'settings.*.key.max' => __('validation.max.string', ['attribute' => __('validation.attributes.key'), 'max' => 150]),
        ];
    }
}
