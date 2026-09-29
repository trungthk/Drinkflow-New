<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Permission;
use App\Enums\PermissionScope;
use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Full permission set of a Superadmin: `permissions[]` keys and `scopes[<key>]` (all|managed).
 */
class SyncSuperadminPermissionsRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True for an active superadmin (the route checks `superadmin.manage`).
     */
    public function authorize(): bool
    {
        return $this->authorizeActiveSuperadmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // Unchecking every box submits no `permissions` field at all: that means "no permission".
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::in(Permission::values())],
            'scopes' => ['sometimes', 'array'],
            'scopes.*' => [Rule::enum(PermissionScope::class)],
        ];
    }

    /**
     * Granted permissions with their scope; scopes default to `all`.
     *
     * @return array<string, string> Permission key => scope value.
     */
    public function grants(): array
    {
        $scopes = (array) $this->validated('scopes', []);
        $grants = [];
        foreach ((array) $this->validated('permissions', []) as $key) {
            $grants[$key] = (string) ($scopes[$key] ?? PermissionScope::All->value);
        }

        return $grants;
    }
}
