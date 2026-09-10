<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user('admin');
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['sometimes', 'nullable', 'string', 'max:30'],
            'note' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
