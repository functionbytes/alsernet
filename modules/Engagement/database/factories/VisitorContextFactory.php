<?php

namespace Modules\Engagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Engagement\Models\VisitorContext;

class VisitorContextFactory extends Factory
{
    protected $model = VisitorContext::class;

    public function definition(): array
    {
        return [
            'session_token' => Str::random(64),
            'customer_id' => null,
            'inbox_id' => 1,
            'context' => [
                'cartValue' => $this->faker->randomFloat(2, 0, 500),
                'itemsCount' => $this->faker->numberBetween(0, 5),
                'currency' => 'EUR',
                'isLoggedIn' => $this->faker->boolean(),
            ],
        ];
    }
}
