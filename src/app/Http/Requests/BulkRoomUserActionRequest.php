<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\RoomUserStatus;
use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkRoomUserActionRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /**
     * Determine whether the current admin may manage room users.
     *
     * @return bool True when the admin has access to the active room.
     */
    public function authorize(): bool
    {
        return $this->authorizeActiveRoomAdmin();
    }

    /**
     * Get validation rules for a bulk room-user action.
     *
     * @return array<string, array<int, mixed>> Validation rules.
     */
    public function rules(): array
    {
        return [
            'room_user_ids' => ['required', 'array', 'min:1'],
            'room_user_ids.*' => ['required', 'integer', 'distinct'],
            'action' => ['required', 'string', Rule::in([
                RoomUserStatus::Active->value,
                RoomUserStatus::Blocked->value,
                RoomUserStatus::Removed->value,
            ])],
        ];
    }
}
