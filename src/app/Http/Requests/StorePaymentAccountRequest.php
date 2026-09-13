<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentAccountRequest extends FormRequest
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
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'bank_code' => [$required, 'string', 'max:30'],
            'bank_name' => [$required, 'string', 'max:120'],
            'account_number' => [$required, 'string', 'max:40'],
            'account_name' => [$required, 'string', 'max:160'],
            'is_default' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'in:active,disabled'],
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
            'bank_code' => __('validation.attributes.bank_code'),
            'bank_name' => __('validation.attributes.bank_name'),
            'account_number' => __('validation.attributes.account_number'),
            'account_name' => __('validation.attributes.account_name'),
            'is_default' => __('validation.attributes.is_default'),
            'status' => __('validation.attributes.status'),
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
            'bank_code.required' => __('validation.required', ['attribute' => __('validation.attributes.bank_code')]),
            'bank_code.max' => __('validation.max.string', ['attribute' => __('validation.attributes.bank_code'), 'max' => 30]),
            'bank_name.required' => __('validation.required', ['attribute' => __('validation.attributes.bank_name')]),
            'bank_name.max' => __('validation.max.string', ['attribute' => __('validation.attributes.bank_name'), 'max' => 120]),
            'account_number.required' => __('validation.required', ['attribute' => __('validation.attributes.account_number')]),
            'account_number.max' => __('validation.max.string', ['attribute' => __('validation.attributes.account_number'), 'max' => 40]),
            'account_name.required' => __('validation.required', ['attribute' => __('validation.attributes.account_name')]),
            'account_name.max' => __('validation.max.string', ['attribute' => __('validation.attributes.account_name'), 'max' => 160]),
        ];
    }
}
