<?php

namespace Modules\Supplier\Database\Factories\Extraction;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Extraction\ExtractionBatch;
use Modules\Supplier\Models\Source\Source;

class ExtractionBatchFactory extends Factory
{
    protected $model = ExtractionBatch::class;

    public function definition(): array
    {
        $total = fake()->numberBetween(0, 500);
        $new = fake()->numberBetween(0, (int) ($total / 2));
        $updated = fake()->numberBetween(0, (int) (($total - $new) / 2));
        $failed = fake()->numberBetween(0, 20);
        $unchanged = max(0, $total - $new - $updated - $failed);

        return [
            'uid' => (string) Str::ulid(),
            'source_id' => Source::factory(),
            'batch_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'batch_type' => fake()->randomElement(['daily', 'manual', 'incremental', 'full_sync']),
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'failed']),
            'total_items' => $total,
            'new_items' => $new,
            'updated_items' => $updated,
            'unchanged_items' => $unchanged,
            'failed_items' => $failed,
            'summary' => null,
            'started_at' => now()->subMinutes(fake()->numberBetween(1, 60)),
            'completed_at' => fake()->optional()->dateTimeBetween('-1 hour', 'now'),
        ];
    }

    public function completed(): static
    {
        return $this->state([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => 'failed',
        ]);
    }
}
