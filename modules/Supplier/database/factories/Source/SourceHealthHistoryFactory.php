<?php

namespace Modules\Supplier\Database\Factories\Source;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Source\Source;
use Modules\Supplier\Models\Source\SourceHealthHistory;

/**
 * @extends Factory<SourceHealthHistory>
 */
class SourceHealthHistoryFactory extends Factory
{
    protected $model = SourceHealthHistory::class;

    public function definition(): array
    {
        $isSuccess = fake()->boolean(70);

        return [
            'source_id' => Source::factory(),
            'check_type' => fake()->randomElement(['connectivity', 'authentication', 'structure', 'content']),
            'is_success' => $isSuccess,
            'status_code' => $isSuccess ? 200 : fake()->randomElement([403, 404, 500, 503]),
            'response_time_ms' => fake()->numberBetween(50, 5000),
            'error_type' => $isSuccess ? null : fake()->randomElement(['timeout', 'http_error', 'parse_error']),
            'error_message' => $isSuccess ? null : fake()->sentence(),
            'page_size_bytes' => fake()->optional()->numberBetween(1000, 500000),
            'products_found' => fake()->optional()->numberBetween(0, 5000),
            'checked_at' => now()->subMinutes(fake()->numberBetween(0, 1440)),
        ];
    }

    public function successful(): static
    {
        return $this->state([
            'is_success' => true,
            'status_code' => 200,
            'error_type' => null,
            'error_message' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'is_success' => false,
            'status_code' => 503,
            'error_type' => 'http_error',
            'error_message' => fake()->sentence(),
        ]);
    }
}
