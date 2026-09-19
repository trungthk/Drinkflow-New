<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ContactTopic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'full_name' => ['required', 'string', 'max:100'],
            'work_email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:20'],
            'company' => ['required', 'string', 'max:150'],
            'topic' => ['required', 'string', Rule::enum(ContactTopic::class)],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ];

        // Only enforce captcha rule if captcha is not disabled and GD extension is available
        if (!config('captcha.disable', false) && extension_loaded('gd') && function_exists('gd_info')) {
            $rules['captcha'] = ['required', 'captcha'];
        }

        return $rules;
    }

    /**
     * Custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'full_name' => __('contact.form.full_name'),
            'work_email' => __('contact.form.work_email'),
            'phone' => __('contact.form.phone'),
            'company' => __('contact.form.company'),
            'topic' => __('contact.form.topic'),
            'message' => __('contact.form.message'),
            'captcha' => __('contact.form.captcha'),
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'captcha.captcha' => __('validation.captcha'),
            'captcha.required' => __('validation.required', ['attribute' => __('contact.form.captcha')]),
        ];
    }
}
