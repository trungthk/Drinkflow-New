<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SplitBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user('admin');
    }

    public function rules(): array
    {
        return [
            'method' => ['required', 'in:by_order,equal,sponsor_first,flat_price,custom'],
            'allocations' => ['required_if:method,custom', 'array'],
            'allocations.*' => ['integer', 'min:0'],
        ];
    }
}
