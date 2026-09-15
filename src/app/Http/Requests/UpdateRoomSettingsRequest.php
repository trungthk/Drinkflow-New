<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomSettingsRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True nếu là Admin đang active và có quyền quản trị phòng.
     */
    public function authorize(): bool
    {
        return $this->authorizeActiveRoomAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:160'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'avatar_url' => ['sometimes', 'nullable', 'url', 'max:1000'],
            'timezone' => ['sometimes', 'required', 'timezone'],
            'language' => ['sometimes', 'required', 'in:vi,en,ja'],
            'default_sponsor' => ['sometimes', 'nullable', 'string', 'max:160'],
            'default_payment_account_id' => ['sometimes', 'nullable', 'integer'],
            'campaign_title_template' => ['sometimes', 'nullable', 'string', 'max:255'],
            'max_campaign_budget' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'personal_debt_ceiling' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'auto_lock_on_debt_limit' => ['sometimes', 'nullable', 'boolean'],
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
            'description' => __('validation.attributes.description'),
            'avatar_url' => __('validation.attributes.items.*.image_url'),
            'timezone' => __('validation.attributes.timezone'),
            'language' => __('validation.attributes.language'),
            'default_sponsor' => __('validation.attributes.default_sponsor'),
            'default_payment_account_id' => __('validation.attributes.default_payment_account_id'),
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
            'name.required' => __('validation.required', ['attribute' => __('validation.attributes.name')]),
            'name.max' => __('validation.max.string', ['attribute' => __('validation.attributes.name'), 'max' => 160]),
            'avatar_url.url' => __('validation.url', ['attribute' => __('validation.attributes.items.*.image_url')]),
            'language.in' => __('validation.in', ['attribute' => __('validation.attributes.language')]),
        ];
    }
}
