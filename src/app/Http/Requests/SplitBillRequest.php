<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class SplitBillRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True nếu là Admin đang active và có quyền quản trị phòng.
     */
    public function authorize(): bool
    {
        return $this->authorizeActiveRoomAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'method' => ['required', 'in:by_order,equal,sponsor_first,flat_price,custom'],
            'allocations' => ['required_if:method,custom', 'array'],
            'allocations.*' => ['integer', 'min:0'],
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
            'method' => __('validation.attributes.method'),
            'allocations' => __('validation.attributes.allocations'),
            'allocations.*' => __('validation.attributes.allocations.*'),
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
            'method.required' => __('validation.required', ['attribute' => __('validation.attributes.method')]),
            'method.in' => __('validation.in', ['attribute' => __('validation.attributes.method')]),
            'allocations.required_if' => __('validation.required_if', ['attribute' => __('validation.attributes.allocations'), 'other' => __('validation.attributes.method'), 'value' => 'custom']),
            'allocations.array' => __('validation.array', ['attribute' => __('validation.attributes.allocations')]),
            'allocations.*.integer' => __('validation.integer', ['attribute' => __('validation.attributes.allocations.*')]),
            'allocations.*.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.allocations.*'), 'min' => 0]),
        ];
    }
}
