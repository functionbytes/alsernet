<?php

namespace Modules\Supplier\Database\Factories\Sync;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Sync\SyncSetting;

/**
 * @extends Factory<SyncSetting>
 */
class SyncSettingFactory extends Factory
{
    protected $model = SyncSetting::class;

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(3),
            'value' => fake()->word(),
            'type' => 'string',
            'label' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
        ];
    }

    public function bool(): static
    {
        return $this->state(['type' => 'bool', 'value' => '1']);
    }

    public function int(): static
    {
        return $this->state(['type' => 'int', 'value' => (string) fake()->numberBetween(1, 1000)]);
    }

    public function json(): static
    {
        return $this->state(['type' => 'json', 'value' => json_encode(['enabled' => true])]);
    }
}
