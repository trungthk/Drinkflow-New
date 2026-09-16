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
            'name' => ['nullable', 'string', 'max:160'],
            'restaurant' => ['required', 'string', 'max:160'],
            'sponsor_type' => ['required', 'in:none,full'],
            'sponsor_description' => ['nullable', 'string', 'max:2000'],
            'sponsor_allocations' => ['nullable', 'array'],
            'sponsor_allocations.*.room_user_id' => ['required', 'integer'],
            'sponsor_allocations.*.percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'deadline' => ['nullable', 'date', 'after:now'],
            'max_budget' => ['required', 'integer', 'min:0'],
            'flat_price' => ['nullable', 'integer', 'min:0'],
            'delivery_fee' => ['nullable', 'integer', 'min:0'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'payment_account_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', 'in:draft,scheduled,active'],
            'items' => ['nullable', 'array', 'max:300'],
            'items.*.name' => ['required', 'string', 'max:200'],
            'items.*.category' => ['nullable', 'string', 'max:100'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.image_url' => ['nullable', 'url:http,https', 'max:1000'],
            'items.*.price' => ['required', 'integer', 'min:0'],
            'items.*.toppings' => ['nullable', 'array', 'max:100'],
            'items.*.toppings.*.name' => ['required', 'string', 'max:200'],
            'items.*.toppings.*.price' => ['required', 'integer', 'min:0'],
            'items.*.options' => ['nullable', 'array', 'max:100'],
            'items.*.options.*.name' => ['required', 'string', 'max:200'],
            'items.*.options.*.price_delta' => ['required', 'integer', 'min:0'],
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
            'sponsor_type' => __('validation.attributes.sponsor_type'),
            'sponsor_description' => __('validation.attributes.sponsor_description'),
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
            'restaurant.required' => __('validation.required', ['attribute' => __('validation.attributes.restaurant')]),
            'deadline.after' => __('validation.after', ['attribute' => __('validation.attributes.deadline'), 'date' => 'now']),
            'max_budget.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.max_budget'), 'min' => 0]),
            'flat_price.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.flat_price'), 'min' => 0]),
            'delivery_fee.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.delivery_fee'), 'min' => 0]),
            'discount.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.discount'), 'min' => 0]),
        ];
    }
}
