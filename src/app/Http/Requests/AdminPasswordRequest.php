<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminPasswordRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['password' => ['required', 'string', 'min:8']]; }
}
