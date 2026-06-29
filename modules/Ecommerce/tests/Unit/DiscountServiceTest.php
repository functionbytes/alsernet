<?php

namespace Modules\Ecommerce\Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Ecommerce\Enums\DiscountType;
use Modules\Ecommerce\Models\Discount;
use Modules\Ecommerce\Services\DiscountService;
use Modules\Ecommerce\Supports\DiscountSupport;
use Tests\TestCase;

class DiscountServiceTest extends TestCase
{
    use DatabaseTransactions;

    private DiscountService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DiscountService(new DiscountSupport);
    }

    public function test_apply_coupon_returns_not_found_for_invalid_code(): void
    {
        $result = $this->service->applyCoupon('INVALID', 100.0);

        $this->assertFalse($result['success']);
        $this->assertSame(0.0, $result['amount']);
        $this->assertNotEmpty($result['message']);
    }

    public function test_apply_coupon_calculates_percentage_discount(): void
    {
        $discount = Discount::factory()->create([
            'code' => 'SAVE10',
            'type' => DiscountType::PERCENTAGE,
            'value' => 10,
            'is_active' => true,
            'min_order_price' => null,
        ]);

        $result = $this->service->applyCoupon('SAVE10', 100.0);

        $this->assertTrue($result['success']);
        $this->assertSame(10.0, $result['amount']);
        $this->assertFalse($result['free_shipping']);
        $this->assertTrue($result['discount']->is($discount));
    }

    public function test_apply_coupon_calculates_fixed_discount(): void
    {
        Discount::factory()->create([
            'code' => 'MINUS20',
            'type' => DiscountType::FIXED,
            'value' => 20,
            'is_active' => true,
        ]);

        $result = $this->service->applyCoupon('MINUS20', 100.0);

        $this->assertTrue($result['success']);
        $this->assertSame(20.0, $result['amount']);
    }

    public function test_apply_coupon_respects_min_order_price(): void
    {
        Discount::factory()->create([
            'code' => 'BIGORDER',
            'type' => DiscountType::PERCENTAGE,
            'value' => 20,
            'is_active' => true,
            'min_order_price' => 500,
        ]);

        $result = $this->service->applyCoupon('BIGORDER', 100.0);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('mínimo', $result['message']);
    }

    public function test_apply_coupon_detects_expired_coupon(): void
    {
        Discount::factory()->create([
            'code' => 'OLD',
            'type' => DiscountType::FIXED,
            'value' => 10,
            'is_active' => true,
            'end_date' => now()->subDay(),
        ]);

        $result = $this->service->applyCoupon('OLD', 100.0);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('expirado', $result['message']);
    }

    public function test_apply_coupon_returns_free_shipping(): void
    {
        Discount::factory()->create([
            'code' => 'FREESHIP',
            'type' => DiscountType::FREE_SHIPPING,
            'value' => 0,
            'is_active' => true,
        ]);

        $result = $this->service->applyCoupon('FREESHIP', 100.0);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['free_shipping']);
        $this->assertSame(0.0, $result['amount']);
    }

    public function test_fixed_discount_cannot_exceed_subtotal(): void
    {
        Discount::factory()->create([
            'code' => 'OVERSIZED',
            'type' => DiscountType::FIXED,
            'value' => 200,
            'is_active' => true,
        ]);

        $result = $this->service->applyCoupon('OVERSIZED', 100.0);

        $this->assertTrue($result['success']);
        $this->assertSame(100.0, $result['amount']);
    }
}
