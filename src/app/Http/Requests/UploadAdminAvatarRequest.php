<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class UploadAdminAvatarRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /**
     * Determine whether the active administrator may upload an avatar.
     *
     * @return bool True when an active administrator is authenticated.
     */
    public function authorize(): bool
    {
        return $this->authorizeActiveAdmin();
    }

    /**
     * Get validation rules for the avatar upload.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'avatar.required' => __('validation.required', [
                'attribute' => __('validation.attributes.avatar'),
            ]),
            'avatar.image' => __('validation.image', [
                'attribute' => __('validation.attributes.avatar'),
            ]),
            'avatar.mimes' => __('validation.mimes', [
                'attribute' => __('validation.attributes.avatar'),
                'values' => 'jpg, jpeg, png, webp',
            ]),
            'avatar.max' => __('validation.max.file', [
                'attribute' => __('validation.attributes.avatar'),
                'max' => 5120,
            ]),
        ];
    }
}
