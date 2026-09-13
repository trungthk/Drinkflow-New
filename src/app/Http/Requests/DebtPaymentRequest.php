<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class DebtPaymentRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True nếu là Admin đang active và có quyền quản trị phòng.
     */
    public function authorize(): bool
    {
        return $this->authorizeActiveRoomAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'string', 'max:30'],
            'reference' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'amount' => __('validation.attributes.amount'),
            'payment_method' => __('validation.attributes.payment_method'),
            'reference' => __('validation.attributes.reference'),
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
            'amount.required' => __('validation.required', ['attribute' => __('validation.attributes.amount')]),
            'amount.integer' => __('validation.integer', ['attribute' => __('validation.attributes.amount')]),
            'amount.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.amount'), 'min' => 1]),
            'payment_method.required' => __('validation.required', ['attribute' => __('validation.attributes.payment_method')]),
            'payment_method.max' => __('validation.max.string', ['attribute' => __('validation.attributes.payment_method'), 'max' => 30]),
            'reference.max' => __('validation.max.string', ['attribute' => __('validation.attributes.reference'), 'max' => 120]),
        ];
    }
}
