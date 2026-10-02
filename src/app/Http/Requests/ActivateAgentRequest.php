<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The invited person choosing its sign-in value on the activation page.
 *
 * The form mirrors the registration form (value + confirmation, minimum length identical), so the
 * same rules protect both ways of joining the platform.
 */
class ActivateAgentRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /**
     * Anyone holding a valid signed link may submit; the route is signed and throttled.
     *
     * @return bool Always true.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'sign_in_value' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
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
            'sign_in_value' => __('validation.attributes.password'),
            'sign_in_value_confirmation' => __('platform.registration.field_password_confirmation'),
        ];
    }

    /**
     * Validated sign-in value to store.
     *
     * @return string Plain sign-in value chosen by the invited person.
     */
    public function signInValue(): string
    {
        return (string) $this->validated('sign_in_value');
    }
}
