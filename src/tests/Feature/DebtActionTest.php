<?php
namespace Tests\Feature;
use App\Actions\Debt\AdjustDebtAction;
use App\Models\Debt;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class DebtActionTest extends TestCase { use RefreshDatabase; public function test_invalid_adjustment_is_rejected_before_mutation(): void { $this->expectException(ValidationException::class); app(AdjustDebtAction::class)->execute(new Debt, 'unknown', -1, ''); } }
