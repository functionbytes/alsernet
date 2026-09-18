<?php

namespace Modules\Supplier\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\SupplierProductPrice;

class SupplierProductPriceFactory extends Factory
{
    protected $model = SupplierProductPrice::class;

    public function definition(): array
    {
        $cost = $this->faker->randomFloat(4, 1, 1000);
        $d1 = $this->faker->randomFloat(3, 0, 30);
        $d2 = $this->faker->randomFloat(3, 0, 15);
        $finalCost = round($cost * (1 - $d1 / 100) * (1 - $d2 / 100), 4);

        return [
            'uid' => Str::ulid(),
            'provider_product_id' => Product::factory(),
            'erp_price_id' => $this->faker->numberBetween(1, 999999),
            'erp_artiprov_id' => $this->faker->numberBetween(1, 999999),
            'cost' => $cost,
            'discount1' => $d1,
            'discount2' => $d2,
            'final_cost' => $finalCost,
            'effective_date' => now()->subDays($this->faker->numberBetween(0, 30)),
            'is_active' => true,
            'is_current' => $this->faker->boolean(70),
            'last_synced_at' => null,
            'erp_updated_at' => now(),
        ];
    }

    public function current(): self
    {
        return $this->state(fn () => ['is_current' => true]);
    }

    public function synced(): self
    {
        return $this->state(fn () => ['last_synced_at' => now()]);
    }
}
