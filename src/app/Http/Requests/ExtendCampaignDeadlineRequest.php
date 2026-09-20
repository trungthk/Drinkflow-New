<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use App\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExtendCampaignDeadlineRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /**
     * Determine whether the current admin may extend a campaign deadline.
     *
     * @return bool True when the admin belongs to the active room.
     */
    public function authorize(): bool
    {
        return $this->authorizeActiveRoomAdmin();
    }

    /**
     * Get the validation rules for the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'minutes' => ['required', 'integer', Rule::in(Campaign::EXTEND_DEADLINE_MINUTES)],
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
            'minutes.required' => __('validation.required', ['attribute' => __('validation.attributes.minutes')]),
            'minutes.integer' => __('validation.integer', ['attribute' => __('validation.attributes.minutes')]),
            'minutes.in' => __('validation.in', ['attribute' => __('validation.attributes.minutes')]),
        ];
    }
}
