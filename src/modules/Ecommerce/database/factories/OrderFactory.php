<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Enums\OrderStatus;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\Order;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 20, 1000);
        $shipping = fake()->randomFloat(2, 0, 50);
        $tax = fake()->randomFloat(2, 0, $subtotal * 0.2);
        $discount = fake()->randomFloat(2, 0, $subtotal * 0.1);
        $total = $subtotal + $shipping + $tax - $discount;

        return [
            'customer_id' => Customer::factory(),
            'status' => fake()->randomElement([OrderStatus::PENDING->value, OrderStatus::PROCESSING->value, OrderStatus::COMPLETED->value]),
            'sub_total' => $subtotal,
            'tax_amount' => $tax,
            'shipping_amount' => $shipping,
            'discount_amount' => $discount,
            'total' => max(0, $total),
            'coupon_code' => fake()->optional(10)->word(),
            'discount_description' => null,
            'shipping_method' => fake()->optional()->word(),
            'shipping_option' => null,
            'payment_method' => fake()->randomElement(['cash', 'card', 'transfer']),
            'payment_status' => fake()->randomElement(['pending', 'paid', 'failed']),
            'customer_note' => fake()->optional()->sentence(),
            'admin_note' => null,
            'completed_at' => fake()->optional()->dateTime(),
        ];
    }
}
