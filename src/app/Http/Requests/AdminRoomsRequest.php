<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class AdminRoomsRequest extends FormRequest
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
            'room_ids' => ['present', 'array'],
            'room_ids.*' => ['integer', 'exists:rooms,id'],
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
            'room_ids' => __('validation.attributes.room_ids'),
            'room_ids.*' => __('validation.attributes.room_ids.*'),
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
            'room_ids.present' => __('validation.present', ['attribute' => __('validation.attributes.room_ids')]),
            'room_ids.array' => __('validation.array', ['attribute' => __('validation.attributes.room_ids')]),
            'room_ids.*.integer' => __('validation.integer', ['attribute' => __('validation.attributes.room_ids.*')]),
            'room_ids.*.exists' => __('validation.exists', ['attribute' => __('validation.attributes.room_ids.*')]),
        ];
    }
}
