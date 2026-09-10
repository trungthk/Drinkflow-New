<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateItemOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user('admin');
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'price' => ['sometimes', 'integer', 'min:0'],
            'price_delta' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:active,hidden'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
