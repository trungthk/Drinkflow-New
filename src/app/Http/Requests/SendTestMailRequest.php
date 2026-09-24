<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class SendTestMailRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /** Maximum length of the free-text body typed in the test mail modal. */
    public const MESSAGE_MAX_LENGTH = 2000;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True when the caller is an active superadmin.
     */
    public function authorize(): bool
    {
        return $this->authorizeActiveSuperadmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'message' => ['nullable', 'string', 'max:'.self::MESSAGE_MAX_LENGTH],
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
            'email' => __('superadmin.system.mail_test_email'),
            'message' => __('superadmin.system.mail_test_message'),
        ];
    }
}
