<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\CustomerAddress;

class CustomerAddressFactory extends Factory
{
    protected $model = CustomerAddress::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'name' => fake()->name(),
            'phone' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'country' => fake()->country(),
            'state' => fake()->state(),
            'city' => fake()->city(),
            'address' => fake()->streetAddress(),
            'zip_code' => fake()->optional()->postcode(),
            'is_default' => false,
            'type' => 'shipping',
        ];
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}
