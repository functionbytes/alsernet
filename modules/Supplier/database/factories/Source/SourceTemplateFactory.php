<?php

namespace Modules\Supplier\Database\Factories\Source;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Source\SourceTemplate;

/**
 * @extends Factory<SourceTemplate>
 */
class SourceTemplateFactory extends Factory
{
    protected $model = SourceTemplate::class;

    public function definition(): array
    {
        return [
            'uid' => (string) Str::ulid(),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->paragraph(),
            'source_type' => fake()->randomElement(['website', 'ftp', 'file', 'api']),
            'connection_template' => ['base_url' => '{{base_url}}', 'timeout' => 30],
            'extraction_template' => ['selectors' => ['title' => '{{title_selector}}']],
            'schedule_template' => ['cron' => '0 6 * * *'],
            'retry_template' => ['max_attempts' => 3, 'strategy' => 'exponential'],
            'validation_template' => ['required' => ['title', 'price']],
            'required_variables' => ['base_url', 'title_selector'],
            'category' => fake()->randomElement(['ecommerce', 'manufacturer', 'distributor', 'marketplace']),
            'tags' => fake()->randomElements(['nike', 'apparel', 'b2b', 'eu'], 2),
            'usage_count' => fake()->numberBetween(0, 100),
            'is_public' => false,
            'created_by' => User::factory(),
        ];
    }

    public function public(): static
    {
        return $this->state(['is_public' => true]);
    }

    public function popular(): static
    {
        return $this->state(['usage_count' => fake()->numberBetween(100, 1000)]);
    }
}
