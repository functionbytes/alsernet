<?php

namespace Modules\Attention\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Attention\Models\AttentionSede;

class AttentionSedeFactory extends Factory
{
    protected $model = AttentionSede::class;

    public function definition(): array
    {
        return [
            'name' => 'Sede '.$this->faker->city(),
            'address' => $this->faker->address(),
            'city' => $this->faker->city(),
            'phone' => $this->faker->numerify('3#########'),
            'email' => $this->faker->unique()->companyEmail(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the sede is inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
