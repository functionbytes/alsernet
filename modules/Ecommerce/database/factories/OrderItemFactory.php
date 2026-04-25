<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\OrderItem;
use Modules\Ecommerce\Models\Product;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $product = Product::inRandomOrder()->first() ?? Product::factory()->create();
        $qty = fake()->numberBetween(1, 5);
        $price = $product->final_price;

        return [
            'order_id' => Order::factory(),
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_image' => $product->featured_image,
            'qty' => $qty,
            'price' => $price,
            'total' => $price * $qty,
            'options' => null,
        ];
    }
}
