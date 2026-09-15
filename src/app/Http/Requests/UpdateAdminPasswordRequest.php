<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateAdminPasswordRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    public function authorize(): bool
    {
        return $this->authorizeActiveAdmin();
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(12)],
            'captcha' => app()->isLocal() || config('captcha.disable')
                ? ['nullable', 'string', 'max:20']
                : ['required', 'string', 'captcha'],
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
            'current_password.required' => __('validation.required', [
                'attribute' => __('validation.attributes.current_password'),
            ]),
            'password.required' => __('validation.required', [
                'attribute' => __('validation.attributes.password'),
            ]),
            'password.confirmed' => __('validation.confirmed', [
                'attribute' => __('validation.attributes.password'),
            ]),
            'captcha.required' => __('validation.required', [
                'attribute' => __('validation.attributes.captcha'),
            ]),
            'captcha.captcha' => __('validation.captcha'),
        ];
    }
}
