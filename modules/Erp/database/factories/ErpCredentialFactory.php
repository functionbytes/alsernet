<?php

namespace Modules\Erp\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Erp\Models\ErpCredential;

class ErpCredentialFactory extends Factory
{
    protected $model = ErpCredential::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true).' '.$this->faker->randomNumber(4),
            'description' => $this->faker->sentence(),
            'auth_type' => $this->faker->randomElement(['none', 'basic', 'bearer', 'api_key']),
            'username' => $this->faker->userName(),
            'password' => $this->faker->password(),
            'token' => $this->faker->sha256(),
            'api_key' => $this->faker->sha1(),
            'api_key_header' => 'X-API-Key',
            'is_active' => true,
        ];
    }
}
