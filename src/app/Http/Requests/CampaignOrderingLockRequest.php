<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class CampaignOrderingLockRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /**
     * Only an active admin of the room may lock or unlock member ordering.
     *
     * @return bool True when the current admin manages the room.
     */
    public function authorize(): bool
    {
        return $this->authorizeActiveRoomAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'notify' => ['nullable', 'boolean'],
        ];
    }
}
