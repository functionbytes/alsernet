<?php

namespace Modules\Supplier\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\SupplierCredential;

class SupplierCredentialFactory extends Factory
{
    protected $model = SupplierCredential::class;

    public function definition(): array
    {
        return [
            'supplier_id' => null,
            'source_id' => null,
            'credential_type' => $this->faker->randomElement(['ftp', 'api', 'oauth', 'basic_auth', 'proxy']),
            'name' => $this->faker->words(2, true),
            // Stored encrypted via the model cast; the array is safe to pass as plain data here.
            'credentials' => [
                'username' => $this->faker->userName(),
                'password' => $this->faker->password(12),
            ],
            'expires_at' => null,
            'last_used_at' => null,
            'is_valid' => true,
            'validation_error' => null,
            'created_by' => null,
        ];
    }
}
