<?php

namespace Modules\Erp\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Erp\Models\ErpEndpoint;

class ErpEndpointFactory extends Factory
{
    protected $model = ErpEndpoint::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.$this->faker->unique()->numberBetween(1, 999999),
            'url' => $this->faker->url(),
            'method' => $this->faker->randomElement(['GET', 'POST', 'PUT', 'PATCH', 'DELETE']),
            'description' => $this->faker->sentence(),
            'timeout' => 30,
            'retry_attempts' => 3,
            'is_active' => true,
            'content_type' => 'application/json',
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function deprecated(): self
    {
        return $this->state(fn () => ['deprecated_at' => now()]);
    }
}
