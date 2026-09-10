<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotificationChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user('admin');
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:chatwork,slack,telegram,webhook'],
            'name' => ['required', 'string', 'max:120'],
            'status' => ['sometimes', 'in:enabled,disabled'],
            'config' => ['sometimes', 'array'],
            'config.*' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
