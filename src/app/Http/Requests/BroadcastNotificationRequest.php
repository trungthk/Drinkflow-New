<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\NotificationType;
use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BroadcastNotificationRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /**
     * Notification types an admin may broadcast from the modal.
     *
     * @return array<int, string>
     */
    public static function allowedTypes(): array
    {
        return [
            NotificationType::AdminBroadcast->value,
            NotificationType::CampaignCreated->value,
            NotificationType::PaymentReminder->value,
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True nếu là Admin đang active và có quyền quản trị phòng.
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
            'type' => ['required', 'string', 'max:80', Rule::in(self::allowedTypes())],
            'title' => ['required', 'string', 'max:160'],
            'body' => ['nullable', 'string', 'max:2000'],
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
            'type' => __('validation.attributes.type'),
            'title' => __('validation.attributes.title'),
            'body' => __('validation.attributes.body'),
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
            'type.required' => __('validation.required', ['attribute' => __('validation.attributes.type')]),
            'type.string' => __('validation.string', ['attribute' => __('validation.attributes.type')]),
            'type.max' => __('validation.max.string', ['attribute' => __('validation.attributes.type'), 'max' => 80]),
            'type.in' => __('validation.in', ['attribute' => __('validation.attributes.type')]),
            'title.required' => __('validation.required', ['attribute' => __('validation.attributes.title')]),
            'title.string' => __('validation.string', ['attribute' => __('validation.attributes.title')]),
            'title.max' => __('validation.max.string', ['attribute' => __('validation.attributes.title'), 'max' => 160]),
            'body.string' => __('validation.string', ['attribute' => __('validation.attributes.body')]),
            'body.max' => __('validation.max.string', ['attribute' => __('validation.attributes.body'), 'max' => 2000]),
        ];
    }
}
