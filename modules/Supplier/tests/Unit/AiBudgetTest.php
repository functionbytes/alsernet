<?php

namespace Modules\Supplier\Tests\Unit;

use Modules\Supplier\Models\Ai\AiBudget;
use Tests\TestCase;

class AiBudgetTest extends TestCase
{
    public function test_usage_percentage_with_zero_limit(): void
    {
        $budget = new AiBudget(['monthly_limit' => 0]);
        $this->assertSame(0.0, $budget->usagePercentage());
    }

    public function test_usage_percentage_with_null_limit(): void
    {
        $budget = new AiBudget(['monthly_limit' => null]);
        $this->assertSame(0.0, $budget->usagePercentage());
    }

    public function test_is_exceeded_method_exists(): void
    {
        $budget = new AiBudget;
        $this->assertTrue(method_exists($budget, 'isExceeded'));
    }

    public function test_is_alert_threshold_reached_method_exists(): void
    {
        $budget = new AiBudget;
        $this->assertTrue(method_exists($budget, 'isAlertThresholdReached'));
    }

    public function test_daily_usage_percentage_method_exists(): void
    {
        $budget = new AiBudget;
        $this->assertTrue(method_exists($budget, 'dailyUsagePercentage'));
    }

    public function test_casts_return_correct_types(): void
    {
        $budget = new AiBudget([
            'monthly_limit' => '100.50',
            'daily_limit' => '25.00',
            'alert_threshold_pct' => '75.00',
            'is_active' => '1',
            'block_on_exceed' => '0',
        ]);

        $this->assertSame('100.50', $budget->monthly_limit);
        $this->assertSame('25.00', $budget->daily_limit);
        $this->assertSame('75.00', $budget->alert_threshold_pct);
        $this->assertTrue($budget->is_active);
        $this->assertFalse($budget->block_on_exceed);
    }
}
