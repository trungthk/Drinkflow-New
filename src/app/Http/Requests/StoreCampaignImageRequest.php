<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignImageRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /**
     * Determine whether the current administrator may upload a campaign image.
     *
     * @return bool True when the administrator actively manages the room.
     */
    public function authorize(): bool
    {
        return $this->authorizeActiveRoomAdmin();
    }

    /**
     * Get validation rules for a campaign menu image.
     *
     * @return array<string, array<int, string>> Upload validation rules.
     */
    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }
}
