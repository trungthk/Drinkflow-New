<?php

namespace App\Http\Requests;

use App\Enums\AdminRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', 'unique:admin_accounts,email'], 'password' => ['required', 'string', 'min:8'], 'role' => ['sometimes', Rule::enum(AdminRole::class)], 'status' => ['sometimes', Rule::in(['active', 'blocked', 'disabled'])], 'room_ids' => ['sometimes', 'array'], 'room_ids.*' => ['integer', 'exists:rooms,id']];
    }
}
