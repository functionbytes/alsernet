<?php

namespace Modules\Supplier\Database\Factories\Source;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Source\Source;
use Modules\Supplier\Models\Source\SourceOption;

/**
 * @extends Factory<SourceOption>
 */
class SourceOptionFactory extends Factory
{
    protected $model = SourceOption::class;

    public function definition(): array
    {
        return [
            'source_id' => Source::factory(),
            'option_key' => fake()->unique()->slug(2),
            'option_value' => fake()->word(),
            'option_type' => SourceOption::TYPE_STRING,
            'is_required' => false,
        ];
    }

    public function url(): static
    {
        return $this->state([
            'option_type' => SourceOption::TYPE_URL,
            'option_value' => fake()->url(),
        ]);
    }

    public function json(): static
    {
        return $this->state([
            'option_type' => SourceOption::TYPE_JSON,
            'option_value' => json_encode(['key' => fake()->word()]),
        ]);
    }

    public function required(): static
    {
        return $this->state(['is_required' => true]);
    }
}
