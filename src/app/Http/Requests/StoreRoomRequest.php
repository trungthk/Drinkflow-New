<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:rooms,slug'], 'description' => ['nullable', 'string'], 'avatar_url' => ['nullable', 'url', 'max:2048'], 'status' => ['sometimes', Rule::in(['active', 'disabled', 'archived'])], 'timezone' => ['sometimes', 'timezone'], 'language' => ['sometimes', 'string', 'max:10'], 'settings' => ['sometimes', 'array']];
    }
}
