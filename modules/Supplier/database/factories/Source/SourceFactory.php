<?php

namespace Modules\Supplier\Database\Factories\Source;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Source\Source;
use Modules\Supplier\Models\Supplier\Supplier;

class SourceFactory extends Factory
{
    protected $model = Source::class;

    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'source_type' => fake()->randomElement(['website', 'ftp', 'file', 'api']),
            'platform' => fake()->randomElement([
                'shopify', 'woocommerce', 'prestashop', 'magento',
                'joomla', 'wordpress', 'nextjs', 'custom', 'blocked',
            ]),
            'label' => fake()->words(3, true),
            'description' => fake()->optional()->paragraph(),
            'trust_level' => fake()->randomElement(['high', 'medium', 'low']),
            'usage_notes' => fake()->optional()->sentence(),
            'priority' => fake()->numberBetween(1, 10),
            'is_active' => true,
            'extraction_mode' => fake()->randomElement(['manual', 'ai']),
            'last_accessed_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'last_processed_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'last_batch_status' => fake()->randomElement(['success', 'partial', 'failed', null]),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function website(): static
    {
        return $this->state(['source_type' => 'website']);
    }

    public function ftp(): static
    {
        return $this->state(['source_type' => 'ftp']);
    }

    public function highTrust(): static
    {
        return $this->state(['trust_level' => 'high']);
    }
}
