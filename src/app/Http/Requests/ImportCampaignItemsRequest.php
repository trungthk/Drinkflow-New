<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class ImportCampaignItemsRequest extends FormRequest
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
            'preview_id' => ['sometimes', 'nullable', 'integer'],
            'source_url' => ['required_without:preview_id', 'nullable', 'url:http,https', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:500'],
            'items.*.name' => ['required', 'string', 'max:200'],
            'items.*.category' => ['nullable', 'string', 'max:100'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.image_url' => [
                'nullable',
                'string',
                'max:1000',
                static function (string $attribute, mixed $value, \Closure $fail): void {
                    if (!is_string($value) || trim($value) === '') {
                        return;
                    }
                    $val = trim($value);
                    if (!filter_var($val, FILTER_VALIDATE_URL) && !str_starts_with($val, '/storage/') && !str_starts_with($val, 'storage/') && !str_starts_with($val, 'uploads/') && !str_starts_with($val, '/uploads/')) {
                        $fail(__('validation.url', ['attribute' => __('validation.attributes.items.*.image_url')]));
                    }
                },
            ],
            'items.*.base_price' => ['required', 'integer', 'min:0'],
            'items.*.source_item_key' => ['nullable', 'string', 'max:255'],
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
            'preview_id' => __('validation.attributes.preview_id'),
            'source_url' => __('validation.attributes.source_url'),
            'items' => __('validation.attributes.items'),
            'items.*.name' => __('validation.attributes.items.*.name'),
            'items.*.category' => __('validation.attributes.items.*.category'),
            'items.*.description' => __('validation.attributes.items.*.description'),
            'items.*.image_url' => __('validation.attributes.items.*.image_url'),
            'items.*.base_price' => __('validation.attributes.items.*.base_price'),
            'items.*.source_item_key' => __('validation.attributes.items.*.source_item_key'),
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
            'source_url.required_without' => __('validation.required_without', ['attribute' => __('validation.attributes.source_url'), 'values' => __('validation.attributes.preview_id')]),
            'source_url.url' => __('validation.url', ['attribute' => __('validation.attributes.source_url')]),
            'items.required' => __('validation.required', ['attribute' => __('validation.attributes.items')]),
            'items.array' => __('validation.array', ['attribute' => __('validation.attributes.items')]),
            'items.min' => __('validation.min.array', ['attribute' => __('validation.attributes.items'), 'min' => 1]),
            'items.max' => __('validation.max.array', ['attribute' => __('validation.attributes.items'), 'max' => 500]),
            'items.*.name.required' => __('validation.required', ['attribute' => __('validation.attributes.items.*.name')]),
            'items.*.name.max' => __('validation.max.string', ['attribute' => __('validation.attributes.items.*.name'), 'max' => 200]),
            'items.*.base_price.required' => __('validation.required', ['attribute' => __('validation.attributes.items.*.base_price')]),
            'items.*.base_price.integer' => __('validation.integer', ['attribute' => __('validation.attributes.items.*.base_price')]),
            'items.*.base_price.min' => __('validation.min.numeric', ['attribute' => __('validation.attributes.items.*.base_price'), 'min' => 0]),
            'items.*.image_url.url' => __('validation.url', ['attribute' => __('validation.attributes.items.*.image_url')]),
        ];
    }
}
