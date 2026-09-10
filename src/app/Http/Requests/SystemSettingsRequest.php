<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SystemSettingsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['settings' => ['required', 'array', 'min:1'], 'settings.*.key' => ['required', 'string', 'max:150'], 'settings.*.value' => ['nullable'], 'settings.*.type' => ['sometimes', Rule::in(['string', 'boolean', 'integer', 'json'])], 'settings.*.is_secret' => ['sometimes', 'boolean']];
    }
}
