<?php

namespace Modules\Engagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Engagement\Models\RecommendationProfile;

class RecommendationProfileFactory extends Factory
{
    protected $model = RecommendationProfile::class;

    public function definition(): array
    {
        return [
            'customer_id' => 1,
            'viewed_products' => [
                [
                    'id' => 'SKU'.$this->faker->numberBetween(1000, 9999),
                    'name' => $this->faker->words(2, true),
                    'category' => 'shoes',
                    'count' => 2,
                    'viewed_at' => now()->toIso8601String(),
                ],
            ],
            'categories' => ['shoes' => 2, 'shirts' => 1],
            'cart_history' => [],
            'last_purchased_at' => null,
        ];
    }
}
