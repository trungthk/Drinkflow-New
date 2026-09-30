<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;
use App\Enums\PlatformPaymentMethod;
use Illuminate\Validation\Rule;

/**
 * Manual payment of a platform invoice recorded by a Superadmin.
 */
class RecordPlatformPaymentRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True for an active superadmin (the route checks `debt.manage`, the controller the Agent scope).
     */
    public function authorize(): bool
    {
        return $this->authorizeActiveSuperadmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1', 'max:10000000000'],
            'method' => ['required', Rule::enum(PlatformPaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:120'],
            'paid_at' => ['nullable', 'date', 'before_or_equal:now'],
            'note' => ['nullable', 'string', 'max:1000'],
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
            'amount' => __('platform.billing.amount'),
            'method' => __('platform.billing.method'),
            'reference' => __('platform.billing.reference'),
            'paid_at' => __('platform.billing.paid_at'),
            'note' => __('platform.billing.note'),
        ];
    }
}
