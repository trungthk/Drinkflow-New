<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CampaignStatus;
use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use App\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignPageRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'status' => [
                'nullable',
                Rule::in(array_merge(
                    ['all'],
                    array_map(static fn(CampaignStatus $status): string => $status->value, CampaignStatus::cases())
                )),
            ],
            'sponsor_type' => ['nullable', Rule::in(['all', ...Campaign::SPONSOR_TYPES])],
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
            'search' => __('validation.attributes.search'),
            'status' => __('validation.attributes.status'),
            'sponsor_type' => __('validation.attributes.sponsor_type'),
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
            'search.string' => __('validation.string', ['attribute' => __('validation.attributes.search')]),
            'search.max' => __('validation.max.string', ['attribute' => __('validation.attributes.search'), 'max' => 255]),
            'status.in' => __('validation.in', ['attribute' => __('validation.attributes.status')]),
            'sponsor_type.in' => __('validation.in', ['attribute' => __('validation.attributes.sponsor_type')]),
        ];
    }
}
