<?php

namespace App\Http\Requests;

use App\Enums\AdminRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminRoleRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['role' => ['required', Rule::enum(AdminRole::class)]]; }
}
