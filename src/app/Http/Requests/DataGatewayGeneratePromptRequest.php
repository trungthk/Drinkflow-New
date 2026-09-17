<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesUserAndAdmin;
use App\Services\DataGateway\DataGatewayConverterService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DataGatewayGeneratePromptRequest extends FormRequest
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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'platform' => ['required', 'string', Rule::in([
                DataGatewayConverterService::PLATFORM_SHOPEE,
                DataGatewayConverterService::PLATFORM_GRAB,
            ])],
            'ai_agent' => ['required', 'string', Rule::in([
                DataGatewayConverterService::AGENT_CHATGPT,
                DataGatewayConverterService::AGENT_GEMINI,
            ])],
            'origin_json' => ['required', 'string', 'json'],
        ];
    }
}
