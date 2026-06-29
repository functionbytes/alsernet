<?php

namespace Modules\Ecommerce\Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Ecommerce\Models\Tax;
use Modules\Ecommerce\Models\TaxRule;
use Modules\Ecommerce\Services\TaxCalculatorService;
use Tests\TestCase;

class TaxCalculatorServiceTest extends TestCase
{
    use DatabaseTransactions;

    private TaxCalculatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TaxCalculatorService;
    }

    public function test_calculate_returns_zero_when_no_tax_configured(): void
    {
        $result = $this->service->calculate(100.0);

        $this->assertSame(0.0, $result['amount']);
        $this->assertSame(0.0, $result['rate']);
    }

    public function test_calculate_uses_default_tax_percentage(): void
    {
        Tax::factory()->create([
            'percentage' => 19,
            'status' => 'published',
        ]);

        $result = $this->service->calculate(100.0);

        $this->assertSame(19.0, $result['amount']);
        $this->assertSame(19.0, $result['rate']);
    }

    public function test_calculate_uses_matching_tax_rule(): void
    {
        $tax = Tax::factory()->create([
            'percentage' => 19,
            'status' => 'published',
        ]);

        TaxRule::factory()->create([
            'tax_id' => $tax->id,
            'price_from' => 0,
            'price_to' => 200,
            'percentage' => 5,
        ]);

        $result = $this->service->calculate(100.0);

        $this->assertSame(5.0, $result['amount']);
        $this->assertSame(5.0, $result['rate']);
    }

    public function test_calculate_falls_back_to_default_when_no_rule_matches(): void
    {
        $tax = Tax::factory()->create([
            'percentage' => 19,
            'status' => 'published',
        ]);

        TaxRule::factory()->create([
            'tax_id' => $tax->id,
            'price_from' => 500,
            'price_to' => 1000,
            'percentage' => 8,
        ]);

        $result = $this->service->calculate(100.0);

        $this->assertSame(19.0, $result['amount']);
        $this->assertSame(19.0, $result['rate']);
    }

    public function test_calculate_uses_rule_with_null_price_to(): void
    {
        $tax = Tax::factory()->create([
            'percentage' => 10,
            'status' => 'published',
        ]);

        TaxRule::factory()->create([
            'tax_id' => $tax->id,
            'price_from' => 50,
            'price_to' => null,
            'percentage' => 7.25,
        ]);

        $result = $this->service->calculate(100.0);

        $this->assertSame(7.25, $result['amount']);
        $this->assertSame(7.25, $result['rate']);
    }

    public function test_calculate_rounds_to_two_decimals(): void
    {
        Tax::factory()->create([
            'percentage' => 12.345,
            'status' => 'published',
        ]);

        $result = $this->service->calculate(99.99);

        $this->assertSame(12.34, $result['amount']);
    }
}
