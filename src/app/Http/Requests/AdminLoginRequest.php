<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\SecurityEvent;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;

class AdminLoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Record a failed captcha as a security event and count it towards the login lockout.
     *
     * The captcha is validated by the `captcha` rule (the single, consuming check), so this is the only place a bad
     * code is seen before the request is rejected.
     *
     * @param Validator $validator Failed validator.
     * @return void
     */
    protected function failedValidation(Validator $validator): void
    {
        if ($validator->errors()->has('captcha')) {
            RateLimiter::hit(strtolower((string) $this->input('email')).'|'.$this->ip(), 60);
            SecurityEvent::create([
                'type' => 'failed_login',
                'severity' => 'medium',
                'ip_address' => $this->ip(),
                'metadata' => ['actor' => 'admin', 'reason' => 'captcha'],
            ]);
        }

        parent::failedValidation($validator);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];

        if (app()->isLocal() || config('captcha.disable')) {
            $rules['captcha'] = ['nullable', 'string', 'max:20'];
        } else {
            $rules['captcha'] = ['required', 'string', 'captcha'];
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => __('validation.attributes.email'),
            'password' => __('validation.attributes.password'),
            'captcha' => __('validation.attributes.captcha'),
            'remember' => __('validation.attributes.remember'),
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
            'email.required' => __('validation.required', ['attribute' => __('validation.attributes.email')]),
            'email.email' => __('validation.email', ['attribute' => __('validation.attributes.email')]),
            'email.max' => __('validation.max.string', ['attribute' => __('validation.attributes.email'), 'max' => 255]),
            'password.required' => __('validation.required', ['attribute' => __('validation.attributes.password')]),
            'captcha.max' => __('validation.max.string', ['attribute' => __('validation.attributes.captcha'), 'max' => 20]),
        ];
    }
}
