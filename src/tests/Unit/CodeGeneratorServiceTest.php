<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Campaign;
use App\Models\Debt;
use App\Models\Order;
use App\Services\Code\CodeGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodeGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test order code format and uniqueness generation.
     */
    public function test_generate_order_code_format(): void
    {
        $service = new CodeGeneratorService();
        $code = $service->generateOrderCode();

        $this->assertNotEmpty($code);
        $this->assertMatchesRegularExpression('/^ORD-\d{8}-[A-Z0-9]{4}$/', $code);
    }

    /**
     * Test campaign code format and uniqueness generation.
     */
    public function test_generate_campaign_code_format(): void
    {
        $service = new CodeGeneratorService();
        $code = $service->generateCampaignCode();

        $this->assertNotEmpty($code);
        $this->assertMatchesRegularExpression('/^CMP-\d{8}-[A-Z0-9]{4}$/', $code);
    }

    /**
     * Test debt code format and uniqueness generation.
     */
    public function test_generate_debt_code_format(): void
    {
        $service = new CodeGeneratorService();
        $code = $service->generateDebtCode();

        $this->assertNotEmpty($code);
        $this->assertMatchesRegularExpression('/^DEB-\d{8}-[A-Z0-9]{4}$/', $code);
    }

    /**
     * Test global user code format and uniqueness generation.
     */
    public function test_generate_global_user_code_format(): void
    {
        $code = CodeGeneratorService::generateGlobalUserCode();

        $this->assertNotEmpty($code);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $code);
    }

    /**
     * Test room user code format and uniqueness generation.
     */
    public function test_generate_room_user_code_format(): void
    {
        $code = CodeGeneratorService::generateRoomUserCode();

        $this->assertNotEmpty($code);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $code);
    }
}
