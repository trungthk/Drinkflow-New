<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $room = $this->route('room');
        return ['name' => ['sometimes', 'string', 'max:255'], 'slug' => ['sometimes', 'string', 'max:255', 'alpha_dash', Rule::unique('rooms', 'slug')->ignore($room)], 'description' => ['nullable', 'string'], 'avatar_url' => ['nullable', 'url', 'max:2048'], 'status' => [Rule::in(['active', 'disabled', 'archived'])], 'timezone' => ['sometimes', 'timezone'], 'language' => ['sometimes', 'string', 'max:10'], 'settings' => ['sometimes', 'array']];
    }
}
