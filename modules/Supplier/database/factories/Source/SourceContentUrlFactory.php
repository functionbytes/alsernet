<?php

namespace Modules\Supplier\Database\Factories\Source;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Source\Source;
use Modules\Supplier\Models\Source\SourceContentUrl;

/**
 * @extends Factory<SourceContentUrl>
 */
class SourceContentUrlFactory extends Factory
{
    protected $model = SourceContentUrl::class;

    public function definition(): array
    {
        return [
            'source_id' => Source::factory(),
            'url' => fake()->url(),
            'note' => fake()->optional()->words(3, true),
            'is_active' => true,
            'priority' => 10,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
