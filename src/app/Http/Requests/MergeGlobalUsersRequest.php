<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class MergeGlobalUsersRequest extends FormRequest
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
            'source_id' => ['required', 'integer', 'exists:global_users,id'],
            'target_id' => ['required', 'integer', 'different:source_id', 'exists:global_users,id'],
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
            'source_id' => __('validation.attributes.source_id'),
            'target_id' => __('validation.attributes.target_id'),
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
            'source_id.required' => __('validation.required', ['attribute' => __('validation.attributes.source_id')]),
            'source_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.source_id')]),
            'target_id.required' => __('validation.required', ['attribute' => __('validation.attributes.target_id')]),
            'target_id.exists' => __('validation.exists', ['attribute' => __('validation.attributes.target_id')]),
            'target_id.different' => __('validation.different', ['attribute' => __('validation.attributes.target_id'), 'other' => __('validation.attributes.source_id')]),
        ];
    }
}
