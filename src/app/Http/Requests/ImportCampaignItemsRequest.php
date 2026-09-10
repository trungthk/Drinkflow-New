<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportCampaignItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user('admin');
    }

    public function rules(): array
    {
        return [
            'preview_id' => ['sometimes', 'nullable', 'integer'],
            'source_url' => ['required_without:preview_id', 'nullable', 'url:http,https', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:500'],
            'items.*.name' => ['required', 'string', 'max:200'],
            'items.*.category' => ['nullable', 'string', 'max:100'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.image_url' => ['nullable', 'url', 'max:1000'],
            'items.*.base_price' => ['required', 'integer', 'min:0'],
            'items.*.source_item_key' => ['nullable', 'string', 'max:255'],
        ];
    }
}
