<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\CustomerPushToken;

class CustomerPushTokenFactory extends Factory
{
    protected $model = CustomerPushToken::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'token' => Str::random(120),
            'platform' => fake()->randomElement(['ios', 'android']),
            'device_id' => Str::uuid()->toString(),
            'app_version' => '1.0.0',
            'locale' => 'es',
            'last_used_at' => now(),
            'is_active' => true,
        ];
    }
}
