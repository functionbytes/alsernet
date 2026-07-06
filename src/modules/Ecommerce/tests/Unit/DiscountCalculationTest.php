<?php

namespace Modules\Ecommerce\Tests\Unit;

use Modules\Ecommerce\Enums\DiscountType;
use Modules\Ecommerce\Models\Discount;
use Tests\TestCase;

/**
 * El importe de un descuento es dinero: debe salir redondeado a 2 decimales y
 * nunca superar el total del pedido. Regresión de dos bugs en calculateDiscount:
 * el porcentaje no redondeaba (céntimos fraccionarios) ni se topaba al total.
 */
class DiscountCalculationTest extends TestCase
{
    private function discount(DiscountType $type, float $value): Discount
    {
        $discount = new Discount;
        $discount->type = $type;
        $discount->value = $value;
        $discount->is_active = true;

        return $discount;
    }

    public function test_percentage_discount_is_rounded_to_two_decimals(): void
    {
        // 99.99 × 33% = 32.9967 → 33.00 (dinero, 2 decimales).
        $this->assertSame(33.0, $this->discount(DiscountType::PERCENTAGE, 33)->calculateDiscount(99.99));
    }

    public function test_percentage_discount_never_exceeds_the_order_total(): void
    {
        // Cupón mal configurado a 150% no puede descontar más que el total.
        $this->assertSame(100.0, $this->discount(DiscountType::PERCENTAGE, 150)->calculateDiscount(100.0));
    }

    public function test_fixed_discount_is_capped_at_the_order_total(): void
    {
        $this->assertSame(30.0, $this->discount(DiscountType::FIXED, 50)->calculateDiscount(30.0));
        $this->assertSame(20.0, $this->discount(DiscountType::FIXED, 20)->calculateDiscount(100.0));
    }
}
