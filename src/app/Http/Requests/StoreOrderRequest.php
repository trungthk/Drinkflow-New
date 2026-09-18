<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True nếu người dùng trong phòng đang active, phòng đang active và đã được gán quyền.
     */
    public function authorize(): bool
    {
        return $this->authorizeActiveRoomUser();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'items'                    => ['required', 'array', 'min:1'],
            'items.*.item_id'          => ['required', 'integer'],
            'items.*.quantity'         => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.size_id'          => ['nullable', 'integer'],
            'items.*.topping_ids'      => ['nullable', 'array'],
            'items.*.topping_ids.*'    => ['integer', 'distinct'],
            'items.*.ice_percent'      => ['nullable', 'integer', 'min:0', 'max:100'],
            'items.*.sugar_percent'    => ['nullable', 'integer', 'min:0', 'max:100'],
            'items.*.note'             => ['nullable', 'string', 'max:500'],
            'items.*.proxy_user_code'  => ['nullable', 'string', 'max:50'],
            'payment_method'           => ['nullable', 'string', 'max:30'],
            'note'                     => ['nullable', 'string', 'max:1000'],
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
            'items' => __('validation.attributes.items'),
            'items.*.item_id' => __('validation.attributes.items.*.item_id'),
            'items.*.quantity' => __('validation.attributes.items.*.quantity'),
            'items.*.size_id' => __('validation.attributes.items.*.size_id'),
            'items.*.topping_ids' => __('validation.attributes.items.*.topping_ids'),
            'items.*.topping_ids.*' => __('validation.attributes.items.*.topping_ids.*'),
            'items.*.ice_percent' => __('validation.attributes.items.*.ice_percent'),
            'items.*.sugar_percent' => __('validation.attributes.items.*.sugar_percent'),
            'items.*.note' => __('validation.attributes.items.*.note'),
            'items.*.proxy_user_code' => __('validation.attributes.items.*.proxy_user_code'),
            'payment_method' => __('validation.attributes.payment_method'),
            'note' => __('validation.attributes.message'),
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
            'items.required' => __('validation.required', ['attribute' => __('validation.attributes.items')]),
            'items.array' => __('validation.array', ['attribute' => __('validation.attributes.items')]),
            'items.min' => __('validation.min.array', ['attribute' => __('validation.attributes.items'), 'min' => 1]),
            'items.*.item_id.required' => __('validation.required', ['attribute' => __('validation.attributes.items.*.item_id')]),
            'items.*.quantity.required' => __('validation.required', ['attribute' => __('validation.attributes.items.*.quantity')]),
            'items.*.quantity.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.items.*.quantity'), 'min' => 1]),
            'items.*.quantity.max' => __('validation.max.numeric', ['attribute' => __('validation.attributes.items.*.quantity'), 'max' => 99]),
        ];
    }
}
