<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BatchUpdateCampaignItemStatusRequest extends FormRequest
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
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.status' => ['required', Rule::in(['active', 'inactive'])],
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
            'items.*.id' => __('validation.attributes.items.*.item_id'),
            'items.*.status' => __('validation.attributes.status'),
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
            'items.*.id.required' => __('validation.required', ['attribute' => __('validation.attributes.items.*.item_id')]),
            'items.*.id.integer' => __('validation.integer', ['attribute' => __('validation.attributes.items.*.item_id')]),
            'items.*.status.required' => __('validation.required', ['attribute' => __('validation.attributes.status')]),
            'items.*.status.in' => __('validation.in', ['attribute' => __('validation.attributes.status')]),
        ];
    }
}
