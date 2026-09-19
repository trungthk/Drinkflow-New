<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCartProxyRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /**
     * Determine whether the current room member may update the cart proxy information.
     *
     * @return bool True when the user and room are active.
     */
    public function authorize(): bool
    {
        return $this->authorizeActiveRoomUser();
    }

    /**
     * Get the validation rules for cart proxy information.
     *
     * @return array<string, array<int, mixed>> Validation rules.
     */
    public function rules(): array
    {
        return [
            'proxy_user_code' => ['nullable', 'string', 'max:50'],
            'proxy_user_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get translated field names for validation feedback.
     *
     * @return array<string, string> Translated attributes.
     */
    public function attributes(): array
    {
        return [
            'proxy_user_code' => __('room.campaign.validation.proxy_user_code'),
            'proxy_user_name' => __('room.campaign.validation.proxy_user_name'),
        ];
    }

    /**
     * Get translated validation messages for cart proxy information.
     *
     * @return array<string, string> Validation messages.
     */
    public function messages(): array
    {
        return [
            'proxy_user_code.string' => __('room.campaign.validation.proxy_user_code_string'),
            'proxy_user_code.max' => __('room.campaign.validation.proxy_user_code_max'),
            'proxy_user_name.string' => __('room.campaign.validation.proxy_user_name_string'),
            'proxy_user_name.max' => __('room.campaign.validation.proxy_user_name_max'),
        ];
    }
}
