<?php

namespace Modules\Supplier\Database\Factories\Source;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Source\Source;
use Modules\Supplier\Models\Source\SourceTransformation;

class SourceTransformationFactory extends Factory
{
    protected $model = SourceTransformation::class;

    public function definition(): array
    {
        return [
            'uid' => Str::ulid(),
            'source_id' => Source::factory(),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->optional()->sentence(),
            'field_name' => $this->faker->randomElement(['title', 'sku', 'price', null]),
            'apply_order' => $this->faker->numberBetween(0, 10),
            'transformation_type' => $this->faker->randomElement(['regex_replace', 'regex_extract', 'mapping', 'split', 'join', 'format']),
            'transformation_config' => [],
            'apply_condition' => null,
            'is_enabled' => true,
        ];
    }

    public function type(string $type, array $config = []): static
    {
        return $this->state(fn (): array => [
            'transformation_type' => $type,
            'transformation_config' => $config,
        ]);
    }

    public function deprecated(string $type = 'formula'): static
    {
        return $this->type($type);
    }
}
