<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class DebtAdjustmentRequest extends FormRequest
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
            'type' => ['required', 'in:increase,decrease,waive,correction'],
            'amount' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'max:1000'],
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
            'type' => __('validation.attributes.type'),
            'amount' => __('validation.attributes.amount'),
            'reason' => __('validation.attributes.reason'),
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
            'type.required' => __('validation.required', ['attribute' => __('validation.attributes.type')]),
            'type.in' => __('validation.in', ['attribute' => __('validation.attributes.type')]),
            'amount.required' => __('validation.required', ['attribute' => __('validation.attributes.amount')]),
            'amount.integer' => __('validation.integer', ['attribute' => __('validation.attributes.amount')]),
            'amount.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.amount'), 'min' => 0]),
            'reason.required' => __('validation.required', ['attribute' => __('validation.attributes.reason')]),
            'reason.max' => __('validation.max.string', ['attribute' => __('validation.attributes.reason'), 'max' => 1000]),
        ];
    }
}
