<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\FeedbackStatus;
use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetFeedbackStatusRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /**
     * Only an active superadmin may approve or deactivate feedback.
     *
     * @return bool True when the current admin is an active superadmin.
     */
    public function authorize(): bool
    {
        return $this->authorizeActiveSuperadmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>> Validation rules.
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::enum(FeedbackStatus::class)],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string> Attribute labels.
     */
    public function attributes(): array
    {
        return [
            'status' => __('validation.attributes.status'),
        ];
    }
}
