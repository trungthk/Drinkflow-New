<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;

class JoinRoomByCodeRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True nếu là Global User đang active.
     */
    public function authorize(): bool
    {
        return $this->authorizeActiveGlobalUser();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $inputUrl = trim((string) ($this->input('room_url') ?? $this->input('room_code')));
        $this->merge(['room_url' => $inputUrl]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'room_url' => ['required', 'url', 'max:500'],
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
            'room_url' => __('validation.attributes.room_url'),
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'room_url.required' => __('global.rooms.url_required'),
            'room_url.url' => __('global.rooms.url_invalid'),
            'room_url.max' => __('validation.max.string', ['attribute' => __('validation.attributes.room_url'), 'max' => 500]),
        ];
    }
}
