<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataGatewayGeneratePromptRequest;
use App\Services\DataGateway\DataGatewayConverterService;
use Illuminate\Http\JsonResponse;

class DataGatewayController extends Controller
{
    /**
     * Return list of supported delivery platforms and AI agents.
     *
     * @param DataGatewayConverterService $service Data gateway converter service.
     * @return JsonResponse Configuration JSON payload.
     */
    public function config(DataGatewayConverterService $service): JsonResponse
    {
        return response()->json([
            'platforms' => array_values($service->getPlatforms()),
            'ai_agents' => array_values($service->getAiAgents()),
            'schema' => $service->getStandardSchema(),
        ]);
    }

    /**
     * Generate generative conversion prompt for AI Agent.
     *
     * @param DataGatewayGeneratePromptRequest $request Validated request.
     * @param DataGatewayConverterService $service Data gateway converter service.
     * @return JsonResponse Generated prompt and agent URL.
     */
    public function generatePrompt(DataGatewayGeneratePromptRequest $request, DataGatewayConverterService $service): JsonResponse
    {
        $originRaw = $request->validated('origin_json');
        $decoded = json_decode($originRaw, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return response()->json([
                'message' => __('admin.data_gateway_err_invalid_json'),
            ], 422);
        }

        $platform = $request->validated('platform');
        $agent = $request->validated('ai_agent');

        $prompt = $service->buildPrompt($platform, $agent, $decoded, app()->getLocale());
        $agentData = $service->getAiAgent($agent);

        return response()->json([
            'success' => true,
            'prompt' => $prompt,
            'agent_url' => $agentData['url'] ?? 'https://chatgpt.com/',
        ]);
    }
}
