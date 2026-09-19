<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicOrderLookupRequest extends FormRequest
{
    /**
     * Normalize the lookup value before validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['identifier' => trim((string) $this->input('identifier', ''))]);
    }

    /**
     * Allow anonymous visitors holding a valid signed campaign link.
     *
     * @return bool Authorization result.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate a single order lookup identifier.
     *
     * @return array<string, array<int, string>> Validation rules.
     */
    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'min:2', 'max:255', 'regex:/^[\pL\pN@+().,_\-\s]+$/u'],
        ];
    }
}
