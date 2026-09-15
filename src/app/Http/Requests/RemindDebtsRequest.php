<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class RemindDebtsRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    public function authorize(): bool
    {
        return $this->authorizeActiveRoomAdmin();
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'debt_ids' => ['nullable', 'array', 'min:1'],
            'debt_ids.*' => ['integer'],
            'campaign_id' => ['nullable', 'integer'],
            'room_user_id' => ['nullable', 'integer'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'all' => ['nullable', 'boolean'],
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
            'debt_ids.array' => __('validation.array', [
                'attribute' => __('validation.attributes.debt_ids'),
            ]),
            'debt_ids.min' => __('validation.min.array', [
                'attribute' => __('validation.attributes.debt_ids'),
                'min' => 1,
            ]),
            'debt_ids.*.integer' => __('validation.integer', [
                'attribute' => __('validation.attributes.debt_ids.*'),
            ]),
            'campaign_id.integer' => __('validation.integer', [
                'attribute' => __('validation.attributes.campaign_id'),
            ]),
            'room_user_id.integer' => __('validation.integer', [
                'attribute' => __('validation.attributes.room_user_id'),
            ]),
            'date.date_format' => __('validation.date_format', [
                'attribute' => __('validation.attributes.date'),
                'format' => 'Y-m-d',
            ]),
            'all.boolean' => __('validation.boolean', [
                'attribute' => __('validation.attributes.all'),
            ]),
        ];
    }
}
