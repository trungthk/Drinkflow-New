<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class StoreItemOptionRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'price' => ['nullable', 'integer', 'min:0'],
            'price_delta' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:active,hidden'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
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
            'name' => __('validation.attributes.name'),
            'price' => __('validation.attributes.price'),
            'price_delta' => __('validation.attributes.price_delta'),
            'status' => __('validation.attributes.status'),
            'sort_order' => __('validation.attributes.sort_order'),
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
            'name.required' => __('validation.required', ['attribute' => __('validation.attributes.name')]),
            'name.max' => __('validation.max.string', ['attribute' => __('validation.attributes.name'), 'max' => 120]),
            'price.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.price'), 'min' => 0]),
            'price_delta.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.price_delta'), 'min' => 0]),
            'sort_order.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.sort_order'), 'min' => 0]),
        ];
    }
}
