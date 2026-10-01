<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignPaymentAccountRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /**
     * Determine whether the current admin may change a campaign's receiving account.
     *
     * @return bool True when the admin belongs to the active room.
     */
    public function authorize(): bool
    {
        return $this->authorizeActiveRoomAdmin();
    }

    /**
     * Get the validation rules for the request.
     *
     * Room ownership and the account status are checked in the action under a row lock.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'payment_account_id' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $attribute = __('validation.attributes.payment_account_id');

        return [
            'payment_account_id.required' => __('validation.required', ['attribute' => $attribute]),
            'payment_account_id.integer' => __('validation.integer', ['attribute' => $attribute]),
            'payment_account_id.min' => __('validation.min.numeric', ['attribute' => $attribute, 'min' => 1]),
        ];
    }
}
