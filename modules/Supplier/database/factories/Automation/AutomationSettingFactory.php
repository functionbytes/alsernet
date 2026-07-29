<?php

namespace Modules\Supplier\Database\Factories\Automation;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Automation\AutomationSetting;

/**
 * @extends Factory<AutomationSetting>
 */
class AutomationSettingFactory extends Factory
{
    protected $model = AutomationSetting::class;

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(3),
            'value' => fake()->word(),
            'type' => 'string',
            'category' => fake()->randomElement(['connection', 'security', 'limits', 'defaults']),
            'description' => fake()->optional()->sentence(),
            'is_sensitive' => false,
            'updated_by' => null,
        ];
    }

    public function boolean(): static
    {
        return $this->state(['type' => 'boolean', 'value' => '1']);
    }

    public function json(): static
    {
        return $this->state(['type' => 'json', 'value' => json_encode(['enabled' => true])]);
    }

    public function sensitive(): static
    {
        return $this->state(['is_sensitive' => true]);
    }
}
