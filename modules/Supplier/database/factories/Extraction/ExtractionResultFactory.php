<?php

namespace Modules\Supplier\Database\Factories\Extraction;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Extraction\ExtractionResult;
use Modules\Supplier\Models\Source\Source;

class ExtractionResultFactory extends Factory
{
    protected $model = ExtractionResult::class;

    public function definition(): array
    {
        $extracted = [
            'product_name' => fake()->words(3, true),
            'title' => fake()->words(3, true),
            'price' => fake()->randomFloat(2, 10, 100),
            'short_description' => fake()->sentence(),
            'long_description' => fake()->paragraph(),
            'specifications' => fake()->sentence(),
            'features' => fake()->sentence(),
            'brand' => fake()->company(),
            'category' => fake()->word(),
        ];

        return [
            'uid' => (string) Str::ulid(),
            'source_id' => Source::factory(),
            'mapping_id' => null,
            'execution_id' => null,
            'batch_id' => null,
            'batch_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'reference' => strtoupper(fake()->bothify('REF-####')),
            'ean' => fake()->optional()->ean13(),
            'source_url' => fake()->optional()->url(),
            'source_file' => null,
            'extracted_data' => $extracted,
            'raw_data' => $extracted,
            'extraction_quality' => fake()->randomElement(['complete', 'partial', 'minimal', 'failed']),
            'missing_fields' => null,
            'status' => fake()->randomElement(['new', 'existing', 'updated', 'error']),
            'hash' => hash('sha256', json_encode($extracted)),
            'previous_hash' => null,
        ];
    }

    public function processed(): static
    {
        return $this->state([
            'status' => 'existing',
            'extraction_quality' => 'complete',
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => 'error',
            'extraction_quality' => 'failed',
        ]);
    }
}
