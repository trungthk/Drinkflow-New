<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VersionRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['version' => ['required', 'string', 'max:50'], 'title' => ['required', 'string', 'max:255'], 'changelog' => ['nullable', 'string'], 'release_date' => ['nullable', 'date'], 'force_refresh' => ['sometimes', 'boolean'], 'important' => ['sometimes', 'boolean']]; }
}
