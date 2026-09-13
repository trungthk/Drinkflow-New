<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
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
            'payment_method' => ['sometimes', 'nullable', 'string', 'max:30'],
            'note' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'items' => ['sometimes', 'array'],
            'items.*.id' => ['required_with:items', 'integer'],
            'items.*.unit_price' => ['required_with:items', 'integer', 'min:0'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
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
            'payment_method' => __('validation.attributes.payment_method'),
            'note' => __('validation.attributes.message'),
            'items' => __('validation.attributes.items'),
            'items.*.id' => __('validation.attributes.items.*.item_id'),
            'items.*.unit_price' => __('validation.attributes.items.*.unit_price'),
            'reason' => __('validation.attributes.reason'),
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
            'payment_method.max' => __('validation.max.string', ['attribute' => __('validation.attributes.payment_method'), 'max' => 30]),
            'note.max' => __('validation.max.string', ['attribute' => __('validation.attributes.message'), 'max' => 1000]),
            'items.array' => __('validation.array', ['attribute' => __('validation.attributes.items')]),
            'items.*.unit_price.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.items.*.unit_price'), 'min' => 0]),
            'reason.max' => __('validation.max.string', ['attribute' => __('validation.attributes.reason'), 'max' => 255]),
        ];
    }
}
