<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminRoomsRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['room_ids' => ['present', 'array'], 'room_ids.*' => ['integer', 'exists:rooms,id']]; }
}
