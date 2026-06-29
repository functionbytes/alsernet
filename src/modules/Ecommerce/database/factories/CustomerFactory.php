<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\Customer;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'),
            'avatar' => null,
            'phone' => fake()->optional()->phoneNumber(),
            'status' => 'active',
            'private_notes' => null,
        ];
    }
}
