<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class VersionRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /** Upper bound for the changelog Markdown, kept under the 64 KB MySQL TEXT column. */
    public const CHANGELOG_MAX_LENGTH = 20000;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True nếu là Superadmin đang active.
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
            'version' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'changelog' => ['nullable', 'string', 'max:'.self::CHANGELOG_MAX_LENGTH],
            'release_date' => ['nullable', 'date'],
            'force_refresh' => ['sometimes', 'boolean'],
            'important' => ['sometimes', 'boolean'],
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
            'version' => __('validation.attributes.version'),
            'title' => __('validation.attributes.title'),
            'changelog' => __('validation.attributes.changelog'),
            'release_date' => __('validation.attributes.release_date'),
            'force_refresh' => __('validation.attributes.force_refresh'),
            'important' => __('validation.attributes.important'),
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
            'version.required' => __('validation.required', ['attribute' => __('validation.attributes.version')]),
            'version.max' => __('validation.max.string', ['attribute' => __('validation.attributes.version'), 'max' => 50]),
            'title.required' => __('validation.required', ['attribute' => __('validation.attributes.title')]),
            'title.max' => __('validation.max.string', ['attribute' => __('validation.attributes.title'), 'max' => 255]),
        ];
    }
}
