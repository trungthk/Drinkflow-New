<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Helpers\FormatHelper;
use PHPUnit\Framework\TestCase;

class FormatHelperTest extends TestCase
{
    /**
     * Test mask method with different input lengths and parameters.
     */
    public function test_mask_helper(): void
    {
        // Shorter or equal to visible count (4)
        $this->assertSame('••••', FormatHelper::mask('1234'));
        $this->assertSame('••', FormatHelper::mask('12'));
        $this->assertSame('', FormatHelper::mask(''));

        // Longer than visible count
        $this->assertSame('••••4382', FormatHelper::mask('00114382'));
        $this->assertSame('•••••••7890', FormatHelper::mask('01234567890'));

        // Custom visible count and mask char
        $this->assertSame('****45', FormatHelper::mask('123445', 2, '*'));
        $this->assertSame('***', FormatHelper::mask('123', 5, '*'));
    }
}
