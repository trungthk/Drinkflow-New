<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SystemNotificationChannelRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['type' => ['required', Rule::in(['chatwork', 'slack', 'telegram', 'webhook'])], 'name' => ['required', 'string', 'max:255'], 'config' => ['required'], 'status' => ['sometimes', Rule::in(['active', 'disabled'])]]; }
}
