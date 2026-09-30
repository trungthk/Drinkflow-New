<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use App\Services\DataGateway\DataGatewayConverterService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DataGatewayAnalyzeRequest extends FormRequest
{
    use AuthorizesUserAndAdmin;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True if authenticated admin has permission in current room.
     */
    public function authorize(): bool
    {
        return $this->authorizeActiveRoomAdmin();
    }

    /**
     * Validate the raw platform JSON sent for internal (agent-free) menu analysis.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Only a hint: the analyzer detects the platform from the JSON structure.
            'platform' => ['nullable', 'string', Rule::in([
                DataGatewayConverterService::PLATFORM_SHOPEE,
                DataGatewayConverterService::PLATFORM_GRAB,
            ])],
            'origin_json' => ['required', 'string', 'json'],
        ];
    }
}
