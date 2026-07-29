<?php

namespace Modules\Supplier\Database\Factories\Supplier;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Supplier\Supplier;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'erp_id' => fake()->unique()->numberBetween(900_000_000, 999_999_999),
            'code' => 'TST-'.Str::upper(Str::random(10)),
            'label' => fake()->company(),
            'description' => fake()->optional()->paragraph(),
            'cif' => fake()->optional()->regexify('[A-Z][0-9]{8}'),
            'email' => fake()->optional()->companyEmail(),
            'available' => true,
            'priority' => fake()->numberBetween(1, 100),
            'metadata' => null,
            'erp_created_at' => now()->subMonths(6),
            'erp_updated_at' => now()->subDays(7),
            'last_sync_at' => now()->subDays(1),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['available' => false]);
    }

    public function highPriority(): static
    {
        return $this->state(['priority' => 1]);
    }

    public function neverSynced(): static
    {
        return $this->state(['last_sync_at' => null]);
    }
}
