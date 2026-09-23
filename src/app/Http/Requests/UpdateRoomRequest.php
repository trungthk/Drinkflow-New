<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

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
        $room = $this->route('room');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'alpha_dash', Rule::unique('rooms', 'slug')->ignore($room)],
            'description' => ['nullable', 'string'],
            'avatar_url' => ['nullable', 'url', 'max:2048'],
            'status' => [Rule::in(['active', 'inactive', 'archived'])],
            'timezone' => ['sometimes', 'timezone'],
            'language' => ['sometimes', 'string', 'max:10'],
            'settings' => ['sometimes', 'array'],
            'admin_ids' => ['sometimes', 'array'],
            'admin_ids.*' => ['integer', 'exists:admin_accounts,id'],
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
            'name' => __('validation.attributes.name'),
            'slug' => __('validation.attributes.slug'),
            'description' => __('validation.attributes.description'),
            'avatar_url' => __('validation.attributes.items.*.image_url'),
            'status' => __('validation.attributes.status'),
            'timezone' => __('validation.attributes.timezone'),
            'language' => __('validation.attributes.language'),
            'settings' => __('validation.attributes.settings'),
            'admin_ids' => __('validation.attributes.admin_ids'),
            'admin_ids.*' => __('validation.attributes.admin_ids.*'),
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
            'name.max' => __('validation.max.string', ['attribute' => __('validation.attributes.name'), 'max' => 255]),
            'slug.alpha_dash' => __('validation.alpha_dash', ['attribute' => __('validation.attributes.slug')]),
            'slug.unique' => __('validation.unique', ['attribute' => __('validation.attributes.slug')]),
            'avatar_url.url' => __('validation.url', ['attribute' => __('validation.attributes.items.*.image_url')]),
        ];
    }
}
