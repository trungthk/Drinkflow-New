<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user('admin');
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:160'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'avatar_url' => ['sometimes', 'nullable', 'url', 'max:1000'],
            'timezone' => ['sometimes', 'required', 'timezone'],
            'language' => ['sometimes', 'required', 'in:vi,en,ja'],
            'default_sponsor' => ['sometimes', 'nullable', 'string', 'max:160'],
            'default_payment_account_id' => ['sometimes', 'nullable', 'integer'],
        ];
    }
}
