<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MergeGlobalUsersRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['source_id' => ['required', 'integer', 'exists:global_users,id'], 'target_id' => ['required', 'integer', 'different:source_id', 'exists:global_users,id']]; }
}
