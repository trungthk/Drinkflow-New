<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignItemRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image_url' => ['nullable', 'url', 'max:1000'],
            'base_price' => ['required', 'integer', 'min:0'],
            'status' => ['nullable', 'in:active,hidden,sold_out,temporarily_unavailable'],
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
            'name' => __('validation.attributes.items.*.name'),
            'category' => __('validation.attributes.items.*.category'),
            'description' => __('validation.attributes.items.*.description'),
            'image_url' => __('validation.attributes.items.*.image_url'),
            'base_price' => __('validation.attributes.items.*.base_price'),
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
            'name.required' => __('validation.required', ['attribute' => __('validation.attributes.items.*.name')]),
            'name.max' => __('validation.max.string', ['attribute' => __('validation.attributes.items.*.name'), 'max' => 200]),
            'base_price.required' => __('validation.required', ['attribute' => __('validation.attributes.items.*.base_price')]),
            'base_price.integer' => __('validation.integer', ['attribute' => __('validation.attributes.items.*.base_price')]),
            'base_price.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.items.*.base_price'), 'min' => 0]),
            'image_url.url' => __('validation.url', ['attribute' => __('validation.attributes.items.*.image_url')]),
        ];
    }
}
