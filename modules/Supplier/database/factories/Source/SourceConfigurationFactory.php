<?php

namespace Modules\Supplier\Database\Factories\Source;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Source\Source;
use Modules\Supplier\Models\Source\SourceConfiguration;

class SourceConfigurationFactory extends Factory
{
    protected $model = SourceConfiguration::class;

    public function definition(): array
    {
        return [
            'uid' => Str::ulid(),
            'source_id' => Source::factory(),
            'config_type' => $this->faker->randomElement(['connection', 'authentication', 'extraction', 'schedule', 'retry', 'proxy', 'validation']),
            'config_data' => [],
            'config_schema_version' => '1.0',
            'is_valid' => true,
            'validation_errors' => null,
            'last_validated_at' => null,
            'is_enabled' => true,
            'priority' => $this->faker->numberBetween(0, 10),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function type(string $type, array $data = []): static
    {
        return $this->state(fn (): array => [
            'config_type' => $type,
            'config_data' => $data,
        ]);
    }
}
