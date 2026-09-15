<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class SettleDebtsRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    public function authorize(): bool
    {
        return $this->authorizeActiveRoomAdmin();
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'debt_ids' => ['nullable', 'array', 'min:1'],
            'debt_ids.*' => ['integer'],
            'campaign_id' => ['nullable', 'integer'],
            'room_user_id' => ['nullable', 'integer'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'all' => ['nullable', 'boolean'],
            'payment_method' => ['required', 'string', 'max:30'],
            'reference' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_method.required' => __('validation.required', ['attribute' => __('validation.attributes.payment_method')]),
            'payment_method.max' => __('validation.max.string', ['attribute' => __('validation.attributes.payment_method'), 'max' => 30]),
            'reference.max' => __('validation.max.string', ['attribute' => __('validation.attributes.reference'), 'max' => 120]),
        ];
    }
}
