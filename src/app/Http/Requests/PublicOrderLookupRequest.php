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

    /**
     * Custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'identifier' => __('public.order_check_placeholder'),
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'identifier.required' => __('validation.required', ['attribute' => __('public.order_check_placeholder')]),
            'identifier.string' => __('validation.string', ['attribute' => __('public.order_check_placeholder')]),
            'identifier.min' => __('validation.min.string', ['attribute' => __('public.order_check_placeholder'), 'min' => 2]),
            'identifier.max' => __('validation.max.string', ['attribute' => __('public.order_check_placeholder'), 'max' => 255]),
            'identifier.regex' => __('public.order_check_invalid'),
        ];
    }
}
