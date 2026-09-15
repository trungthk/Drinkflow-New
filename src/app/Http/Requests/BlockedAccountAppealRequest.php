<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class BlockedAccountAppealRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    public function authorize(): bool
    {
        return $this->user('web') !== null || $this->attributes->get('global_user') !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:1000'],
            'attachment_note' => ['nullable', 'string', 'max:500'],
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
            'reason.max' => __('validation.max.string', [
                'attribute' => __('validation.attributes.reason'),
                'max' => 1000,
            ]),
            'attachment_note.max' => __('validation.max.string', [
                'attribute' => __('validation.attributes.attachment_note'),
                'max' => 500,
            ]),
        ];
    }
}
