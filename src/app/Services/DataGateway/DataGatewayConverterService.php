<?php

declare(strict_types=1);

namespace App\Services\DataGateway;

use InvalidArgumentException;

class DataGatewayConverterService
{
    public const PLATFORM_SHOPEE = 'shopee';
    public const PLATFORM_GRAB = 'grab';

    public const AGENT_CHATGPT = 'chatgpt';
    public const AGENT_GEMINI = 'gemini';

    /**
     * Get all supported delivery platforms.
     *
     * @return array<string, array<string, string>> List of supported platforms.
     */
    public function getPlatforms(): array
    {
        return [
            self::PLATFORM_SHOPEE => [
                'id' => self::PLATFORM_SHOPEE,
                'name' => 'ShopeeFood (Now / DeliveryNow)',
                'instruction' => __('admin.data_gateway_shopee_instruction'),
                'api_prefix' => 'get_delivery_dishes',
            ],
            self::PLATFORM_GRAB => [
                'id' => self::PLATFORM_GRAB,
                'name' => 'GrabFood',
                'instruction' => __('admin.data_gateway_grab_instruction'),
                'api_prefix' => 'https://portal.grab.com/foodweb/guest/v2/merchants/',
            ],
        ];
    }

    /**
     * Get all supported AI Agents.
     *
     * @return array<string, array<string, string>> List of supported AI agents.
     */
    public function getAiAgents(): array
    {
        return [
            self::AGENT_CHATGPT => [
                'id' => self::AGENT_CHATGPT,
                'name' => 'OpenAI ChatGPT (GPT-4o / GPT-4o-mini)',
                'display_name' => 'ChatGPT',
                'url' => 'https://chatgpt.com/',
            ],
            self::AGENT_GEMINI => [
                'id' => self::AGENT_GEMINI,
                'name' => 'Google Gemini (Gemini 1.5 Pro / Flash)',
                'display_name' => 'Gemini',
                'url' => 'https://gemini.google.com/',
            ],
        ];
    }

    /**
     * Get details for a specific platform.
     *
     * @param string $platform Platform identifier.
     * @return array<string, string>|null Platform details or null if not found.
     */
    public function getPlatform(string $platform): ?array
    {
        return $this->getPlatforms()[$platform] ?? null;
    }

    /**
     * Get details for a specific AI agent.
     *
     * @param string $agent Agent identifier.
     * @return array<string, string>|null Agent details or null if not found.
     */
    public function getAiAgent(string $agent): ?array
    {
        return $this->getAiAgents()[$agent] ?? null;
    }

    /**
     * Get the standardized DrinkFlow Menu JSON Schema example.
     *
     * @return string Formatted compact JSON Schema string.
     */
    public function getStandardSchema(): string
    {
        $schema = [
            [
                'name' => 'Tên món',
                'price' => 35000,
                'category' => 'Danh mục',
                'description' => '',
                'image_url' => '',
                'toppings' => [
                    [
                        'name' => 'Topping',
                        'price' => 5000,
                    ],
                ],
                'options' => [
                    [
                        'name' => 'Size L',
                        'price_delta' => 5000,
                    ],
                ],
            ],
        ];

        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
    }

    /**
     * Build the lightweight, token-optimized multilingual generative conversion prompt.
     *
     * @param string $platform Platform identifier (shopee, grab).
     * @param string $agent AI Agent identifier (chatgpt, gemini).
     * @param mixed $originJson Raw or parsed JSON payload.
     * @param string|null $locale Target locale for prompt instructions.
     * @return string Complete compact prompt for AI conversion.
     * @throws InvalidArgumentException When platform or agent is invalid.
     */
    public function buildPrompt(string $platform, string $agent, mixed $originJson, ?string $locale = null): string
    {
        $platformData = $this->getPlatform($platform);
        if ($platformData === null) {
            throw new InvalidArgumentException("Platform '{$platform}' is unsupported.");
        }

        $agentData = $this->getAiAgent($agent);
        if ($agentData === null) {
            throw new InvalidArgumentException("AI Agent '{$agent}' is unsupported.");
        }

        $activeLocale = $locale ?? app()->getLocale();
        $platformName = $platformData['name'];

        $personaText = __('admin.data_gateway_prompt_persona', ['platform' => $platformName], $activeLocale);
        $schemaRules = __('admin.data_gateway_prompt_schema_rules', [], $activeLocale);
        $strictTerms = __('admin.data_gateway_prompt_strict_terms', [], $activeLocale);
        $dataHeader = __('admin.data_gateway_prompt_data_header', ['platform' => $platformName], $activeLocale);

        $schemaExample = $this->getStandardSchema();

        $formattedOrigin = is_string($originJson)
            ? (json_encode(json_decode($originJson, true) ?? $originJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $originJson)
            : (json_encode($originJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');

        return implode("\n\n", [
            $personaText,
            "{$schemaRules}\n{$schemaExample}",
            $strictTerms,
            "{$dataHeader}\n{$formattedOrigin}",
        ]);
    }
}
