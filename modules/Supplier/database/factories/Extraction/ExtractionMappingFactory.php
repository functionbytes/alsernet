<?php

namespace Modules\Supplier\Database\Factories\Extraction;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Extraction\ExtractionMapping;
use Modules\Supplier\Models\Source\Source;

class ExtractionMappingFactory extends Factory
{
    protected $model = ExtractionMapping::class;

    public function definition(): array
    {
        return [
            'uid' => (string) Str::ulid(),
            'source_id' => Source::factory(),
            'name' => fake()->words(3, true).' mapping',
            'source_type' => fake()->randomElement([
                'website', 'ftp_excel', 'ftp_csv', 'ftp_pdf', 'upload_pdf', 'upload_excel', 'api',
            ]),
            'field_mappings' => [
                'name' => ['selector' => '.product-title', 'attribute' => 'text'],
                'price' => ['selector' => '.price', 'attribute' => 'text'],
                'sku' => ['selector' => '[data-sku]', 'attribute' => 'data-sku'],
            ],
            'search_config' => ['list_selector' => '.product-grid .item', 'pagination' => '.next-page'],
            'validation_rules' => ['name' => ['required' => true], 'price' => ['numeric' => true]],
            'transform_rules' => ['price' => ['cast' => 'float'], 'name' => ['trim' => true]],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function ofType(string $sourceType): static
    {
        return $this->state(['source_type' => $sourceType]);
    }
}
