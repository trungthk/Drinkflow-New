<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Constants\AppLocale;
use App\Enums\RoomStatus;
use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Room form of an Agent (create and edit its own rooms). Ownership is checked by the controller policy.
 */
class SaveAgentRoomRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True for an active admin.
     */
    public function authorize(): bool
    {
        return $this->authorizeActiveAdmin();
    }

    /**
     * Derive the slug from the name when it is left empty.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $slug = trim((string) $this->input('slug'));
        $this->merge(['slug' => Str::slug($slug !== '' ? $slug : (string) $this->input('name'))]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $room = $this->route('room');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::notIn(Room::RESERVED_SLUGS), Rule::unique('rooms', 'slug')->ignore($room instanceof Room ? $room->id : null)],
            'description' => ['nullable', 'string', 'max:2000'],
            'timezone' => ['required', 'timezone'],
            'language' => ['required', Rule::in(array_keys(AppLocale::SUPPORTED))],
            // Archiving has its own action so the quota slot is released explicitly.
            'status' => ['sometimes', Rule::in([RoomStatus::Active->value, RoomStatus::Inactive->value])],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('validation.attributes.name'),
            'slug' => __('validation.attributes.slug'),
            'description' => __('validation.attributes.description'),
            'timezone' => __('validation.attributes.timezone'),
            'language' => __('validation.attributes.language'),
            'status' => __('validation.attributes.status'),
        ];
    }
}
