<?php

namespace Modules\Supplier\Database\Factories\Automation;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Automation\AutomationVariable;

/**
 * @extends Factory<AutomationVariable>
 */
class AutomationVariableFactory extends Factory
{
    protected $model = AutomationVariable::class;

    public function definition(): array
    {
        return [
            'uid' => (string) Str::ulid(),
            'name' => fake()->unique()->slug(2),
            'description' => fake()->optional()->sentence(),
            'scope' => 'global',
            'scope_id' => null,
            'variable_type' => 'string',
            'value' => fake()->word(),
            'encrypted_value' => null,
            'validation_regex' => null,
            'allowed_values' => null,
            'is_required' => false,
            'default_value' => null,
            'is_system' => false,
            'is_sensitive' => false,
        ];
    }

    public function global(): static
    {
        return $this->state(['scope' => 'global', 'scope_id' => null]);
    }

    public function supplierScoped(int $supplierId = 1): static
    {
        return $this->state(['scope' => 'supplier', 'scope_id' => $supplierId]);
    }

    public function system(): static
    {
        return $this->state(['is_system' => true]);
    }

    public function secret(): static
    {
        return $this->state([
            'variable_type' => 'secret',
            'value' => null,
            'encrypted_value' => null,
            'is_sensitive' => true,
        ]);
    }
}
