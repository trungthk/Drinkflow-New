<?php

declare(strict_types=1);

namespace App\Http\Requests;

class UpdateCampaignRequest extends StoreCampaignRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'deadline' => ['nullable', 'date'],
            'status' => ['sometimes', 'in:draft,scheduled,active,closed,cancelled,archived'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.price' => ['nullable', 'integer', 'min:0'],
            'items.*.base_price' => ['nullable', 'integer', 'min:0'],
            'items.*.sizes' => ['nullable', 'array', 'max:100'],
            'items.*.sizes.*.name' => ['required_with:items.*.sizes', 'string', 'max:200'],
            'items.*.sizes.*.price_delta' => ['required_with:items.*.sizes', 'integer', 'min:0'],
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'status' => __('validation.attributes.status'),
        ]);
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'status.in' => __('validation.in', ['attribute' => __('validation.attributes.status')]),
        ]);
    }
}
