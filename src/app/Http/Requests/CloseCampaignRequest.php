<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class CloseCampaignRequest extends FormRequest
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
            'allow_debt' => ['sometimes', 'boolean'],
            'reason' => ['nullable', 'string', 'max:1000'],
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
            'allow_debt.boolean' => __('validation.boolean', [
                'attribute' => __('validation.attributes.allow_debt'),
            ]),
            'reason.max' => __('validation.max.string', [
                'attribute' => __('validation.attributes.reason'),
                'max' => 1000,
            ]),
        ];
    }
}
