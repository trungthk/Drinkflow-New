<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\CampaignStatus;
use App\Models\AdminAccount;
use App\Models\Campaign;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataGatewayConverterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verify that campaign create page renders Data Gateway AI tab, components, and instructions.
     *
     * @return void
     */
    public function test_campaign_create_page_renders_data_gateway_converter_tab_and_instructions(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Gateway Admin',
            'email' => 'gateway-admin@example.test',
            'password' => 'secret-password',
            'role' => AdminRole::Admin,
            'status' => 'active',
        ]);
        $room = Room::create([
            'name' => 'Tech Room',
            'slug' => 'tech-room',
            'status' => 'active',
        ]);
        $admin->rooms()->attach($room);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.campaigns.create', $room));

        $response->assertOk();
        $response->assertSee(__('admin.source_data_gateway'));
        $response->assertSee(__('admin.data_gateway_title'));
        $response->assertSee(__('admin.data_gateway_badge'));
        $response->assertSee(__('admin.data_gateway_instruction_title'));
        $response->assertSee(__('admin.data_gateway_btn_copy_prompt'));
        $response->assertSee(__('admin.data_gateway_btn_import_file'));
        $response->assertSee(__('admin.data_gateway_btn_apply_to_menu'));
        $response->assertSee('data-gateway-converter', false);
        $response->assertSee('selectedPlatform', false);
        $response->assertSee('copyPromptAndOpenAgent()', false);
        $response->assertSee('importResultFile($event)', false);
        $response->assertDontSee(__('admin.source_json'));
    }

    /**
     * Verify that campaign edit page renders Data Gateway AI tab, components, and instructions.
     *
     * @return void
     */
    public function test_campaign_edit_page_renders_data_gateway_converter_tab_and_instructions(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Gateway Admin Edit',
            'email' => 'gateway-admin-edit@example.test',
            'password' => 'secret-password',
            'role' => AdminRole::Admin,
            'status' => 'active',
        ]);
        $room = Room::create([
            'name' => 'Coffee Room',
            'slug' => 'coffee-room',
            'status' => 'active',
        ]);
        $admin->rooms()->attach($room);

        $campaign = Campaign::create([
            'room_id' => $room->id,
            'name' => 'Milktea Fest',
            'restaurant' => 'KOI The',
            'status' => CampaignStatus::Draft,
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.campaigns.edit', [$room, $campaign]));

        $response->assertOk();
        $response->assertSee(__('admin.source_data_gateway'));
        $response->assertSee(__('admin.data_gateway_title'));
        $response->assertSee(__('admin.data_gateway_badge'));
        $response->assertSee(__('admin.data_gateway_instruction_title'));
        $response->assertSee(__('admin.data_gateway_btn_copy_prompt'));
        $response->assertSee(__('admin.data_gateway_btn_import_file'));
        $response->assertSee(__('admin.data_gateway_btn_apply_to_menu'));
        $response->assertSee('data-gateway-converter', false);
        $response->assertSee('selectedPlatform', false);
        $response->assertSee('copyPromptAndOpenAgent()', false);
        $response->assertSee('importResultFile($event)', false);
        $response->assertDontSee(__('admin.source_json'));
    }

    /**
     * Verify that Data Gateway translation keys exist across all 3 supported locales (vi, en, ja).
     *
     * @return void
     */
    public function test_data_gateway_localization_keys_are_consistent_across_all_locales(): void
    {
        $keys = [
            'admin.source_data_gateway',
            'admin.data_gateway_badge',
            'admin.data_gateway_title',
            'admin.data_gateway_desc',
            'admin.data_gateway_platform_label',
            'admin.data_gateway_ai_label',
            'admin.data_gateway_instruction_title',
            'admin.data_gateway_shopee_instruction',
            'admin.data_gateway_grab_instruction',
            'admin.data_gateway_origin_json_label',
            'admin.data_gateway_origin_json_placeholder',
            'admin.data_gateway_btn_copy_prompt',
            'admin.data_gateway_btn_copy_and_open_agent',
            'admin.data_gateway_btn_format_json',
            'admin.data_gateway_btn_clear',
            'admin.data_gateway_ai_result_label',
            'admin.data_gateway_ai_result_placeholder',
            'admin.data_gateway_btn_import_file',
            'admin.data_gateway_btn_apply_to_menu',
            'admin.data_gateway_err_empty_origin',
            'admin.data_gateway_err_invalid_json',
            'admin.data_gateway_success_copy_prompt',
            'admin.data_gateway_success_apply_menu',
            'admin.data_gateway_success_import_file',
            'admin.data_gateway_err_empty_ai_result',
            'admin.data_gateway_err_invalid_ai_result',
            'admin.data_gateway_chars_count',
            'admin.data_gateway_paste_tip',
            'admin.data_gateway_menu_expiry_title',
            'admin.data_gateway_menu_expiry_desc',
        ];

        $promptKeys = [
            'admin.data_gateway_prompt_persona',
            'admin.data_gateway_prompt_schema_rules',
            'admin.data_gateway_prompt_strict_terms',
            'admin.data_gateway_prompt_data_header',
        ];

        $originalLocale = app()->getLocale();
        try {
            foreach (['vi', 'en', 'ja'] as $locale) {
                app()->setLocale($locale);
                foreach (array_merge($keys, $promptKeys) as $key) {
                    $translated = __($key);
                    $this->assertNotSame($key, $translated, "Missing translation for key '{$key}' in locale '{$locale}'.");
                    $this->assertNotEmpty($translated, "Translation for key '{$key}' is empty in locale '{$locale}'.");
                }
            }
        } finally {
            app()->setLocale($originalLocale);
        }
    }

    /**
     * Verify that DataGatewayConverterService returns registered platforms and agents, and builds prompt correctly.
     *
     * @return void
     */
    public function test_data_gateway_converter_service_builds_valid_prompt_for_all_locales(): void
    {
        /** @var \App\Services\DataGateway\DataGatewayConverterService $service */
        $service = app(\App\Services\DataGateway\DataGatewayConverterService::class);

        $platforms = $service->getPlatforms();
        $this->assertArrayHasKey('shopee', $platforms);
        $this->assertArrayHasKey('grab', $platforms);

        $agents = $service->getAiAgents();
        $this->assertArrayHasKey('chatgpt', $agents);
        $this->assertArrayHasKey('gemini', $agents);

        $rawJson = json_encode([
            'restaurant' => 'KOI Thé Cafe',
            'dishes' => [
                ['name' => 'Trà sữa Trân Châu Hoàng Kim', 'price' => 50000],
            ],
        ], JSON_THROW_ON_ERROR);

        $originalLocale = app()->getLocale();
        try {
            foreach (['vi', 'en', 'ja'] as $locale) {
                app()->setLocale($locale);

                $prompt = $service->buildPrompt('shopee', 'chatgpt', $rawJson);
                $this->assertStringContainsString('category', $prompt);
                $this->assertStringContainsString('toppings', $prompt);
                $this->assertStringContainsString('options', $prompt);
                $this->assertStringContainsString('menu.md', $prompt);
                $this->assertStringContainsString('KOI Thé Cafe', $prompt);
                $this->assertStringContainsString('Trà sữa Trân Châu Hoàng Kim', $prompt);
            }
        } finally {
            app()->setLocale($originalLocale);
        }
    }

    /**
     * Verify that admin can fetch data gateway configuration via API.
     *
     * @return void
     */
    public function test_admin_can_fetch_data_gateway_config(): void
    {
        $admin = AdminAccount::create([
            'name' => 'API Admin',
            'email' => 'api-admin@example.test',
            'password' => 'secret-password',
            'role' => AdminRole::Admin,
            'status' => 'active',
        ]);
        $room = Room::create([
            'name' => 'API Room',
            'slug' => 'api-room',
            'status' => 'active',
        ]);
        $admin->rooms()->attach($room);

        $response = $this->actingAs($admin, 'admin')->getJson(route('admin.data-gateway.config', $room));

        $response->assertOk();
        $response->assertJsonStructure([
            'platforms' => [
                '*' => ['id', 'name', 'instruction', 'api_prefix'],
            ],
            'ai_agents' => [
                '*' => ['id', 'name', 'display_name', 'url'],
            ],
            'schema',
        ]);
    }

    /**
     * Verify that admin can generate prompt via API.
     *
     * @return void
     */
    public function test_admin_can_generate_prompt_via_api(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Prompt Admin',
            'email' => 'prompt-admin@example.test',
            'password' => 'secret-password',
            'role' => AdminRole::Admin,
            'status' => 'active',
        ]);
        $room = Room::create([
            'name' => 'Prompt Room',
            'slug' => 'prompt-room',
            'status' => 'active',
        ]);
        $admin->rooms()->attach($room);

        $payload = [
            'platform' => 'shopee',
            'ai_agent' => 'chatgpt',
            'origin_json' => json_encode(['mock_dish_id' => 12345, 'name' => 'Trà xanh'], JSON_THROW_ON_ERROR),
        ];

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.data-gateway.generate-prompt', $room), $payload);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'prompt',
            'agent_url',
        ]);
        $this->assertTrue($response->json('success'));
        $this->assertStringContainsString('Trà xanh', $response->json('prompt'));
        $this->assertSame('https://chatgpt.com/', $response->json('agent_url'));
    }

    /**
     * Verify that API returns 422 if origin_json is invalid JSON syntax or platform is invalid.
     *
     * @return void
     */
    public function test_admin_cannot_generate_prompt_with_invalid_payload(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Validation Admin',
            'email' => 'validation-admin@example.test',
            'password' => 'secret-password',
            'role' => AdminRole::Admin,
            'status' => 'active',
        ]);
        $room = Room::create([
            'name' => 'Validation Room',
            'slug' => 'validation-room',
            'status' => 'active',
        ]);
        $admin->rooms()->attach($room);

        $invalidJsonPayload = [
            'platform' => 'shopee',
            'ai_agent' => 'chatgpt',
            'origin_json' => '{ invalid json string ',
        ];

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.data-gateway.generate-prompt', $room), $invalidJsonPayload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['origin_json']);

        $invalidPlatformPayload = [
            'platform' => 'unsupported_platform',
            'ai_agent' => 'chatgpt',
            'origin_json' => '{"valid": true}',
        ];

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.data-gateway.generate-prompt', $room), $invalidPlatformPayload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['platform']);
    }
}

