<?php

namespace App\Http\Requests;

use App\Enums\AdminRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $admin = $this->route('admin');
        return ['name' => ['sometimes', 'string', 'max:255'], 'email' => ['sometimes', 'email', 'max:255', Rule::unique('admin_accounts', 'email')->ignore($admin)], 'password' => ['sometimes', 'nullable', 'string', 'min:8'], 'role' => ['sometimes', Rule::enum(AdminRole::class)], 'status' => ['sometimes', Rule::in(['active', 'blocked', 'disabled'])], 'room_ids' => ['sometimes', 'array'], 'room_ids.*' => ['integer', 'exists:rooms,id']];
    }
}
