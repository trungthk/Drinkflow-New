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
            'status' => ['sometimes', 'in:draft,scheduled,active,closed,cancelled,archived'],
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
