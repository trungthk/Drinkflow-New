<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:160'],
            'restaurant' => ['required', 'string', 'max:160'],
            'sponsor_name' => ['nullable', 'string', 'max:160'],
            'deadline' => ['nullable', 'date', 'after:now'],
            'max_budget' => ['nullable', 'integer', 'min:0'],
            'flat_price' => ['nullable', 'integer', 'min:0'],
            'delivery_fee' => ['nullable', 'integer', 'min:0'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'payment_account_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', 'in:draft,scheduled,active'],
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
            'name' => __('validation.attributes.title'),
            'restaurant' => __('validation.attributes.restaurant'),
            'sponsor_name' => __('validation.attributes.sponsor_name'),
            'deadline' => __('validation.attributes.deadline'),
            'max_budget' => __('validation.attributes.max_budget'),
            'flat_price' => __('validation.attributes.flat_price'),
            'delivery_fee' => __('validation.attributes.delivery_fee'),
            'discount' => __('validation.attributes.discount'),
            'payment_account_id' => __('validation.attributes.payment_account_id'),
            'description' => __('validation.attributes.description'),
            'status' => __('validation.attributes.status'),
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
            'name.required' => __('validation.required', ['attribute' => __('validation.attributes.title')]),
            'restaurant.required' => __('validation.required', ['attribute' => __('validation.attributes.restaurant')]),
            'deadline.after' => __('validation.after', ['attribute' => __('validation.attributes.deadline'), 'date' => 'now']),
            'max_budget.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.max_budget'), 'min' => 0]),
            'flat_price.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.flat_price'), 'min' => 0]),
            'delivery_fee.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.delivery_fee'), 'min' => 0]),
            'discount.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.discount'), 'min' => 0]),
        ];
    }
}
