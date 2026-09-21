<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use App\Support\Security\OutboundUrlGuard;
use Illuminate\Foundation\Http\FormRequest;

class NotificationChannelRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

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
            'type' => ['required', 'in:chatwork,slack,telegram,webhook'],
            'name' => ['required', 'string', 'max:120'],
            'status' => ['sometimes', 'in:enabled,disabled'],
            'config' => ['sometimes', 'array'],
            'config.*' => ['nullable', 'string', 'max:2000'],
            'config.webhook_url' => [
                'nullable',
                'string',
                'max:2000',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || trim($value) === '') {
                        return;
                    }
                    $guard = app(OutboundUrlGuard::class);
                    if ($this->input('type') === 'slack' && ! $guard->isSafe($value, ['hooks.slack.com'])) {
                        $fail(__('admin.slack_webhook_host_invalid'));

                        return;
                    }
                    if (! $guard->isSafe($value)) {
                        $fail(__('admin.webhook_url_unsafe'));
                    }
                },
            ],
            'config.bot_token' => ['nullable', 'string', 'max:2000', 'regex:/^\d+:[A-Za-z0-9_-]+$/'],
            'config.room_id' => ['nullable', 'string', 'max:2000', 'regex:/^\d+$/'],
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
            'name' => __('validation.attributes.name'),
            'status' => __('validation.attributes.status'),
            'config' => __('validation.attributes.config'),
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
            'type.in' => __('validation.in', ['attribute' => __('validation.attributes.type')]),
            'name.required' => __('validation.required', ['attribute' => __('validation.attributes.name')]),
            'name.max' => __('validation.max.string', ['attribute' => __('validation.attributes.name'), 'max' => 120]),
            'status.in' => __('validation.in', ['attribute' => __('validation.attributes.status')]),
        ];
    }
}
