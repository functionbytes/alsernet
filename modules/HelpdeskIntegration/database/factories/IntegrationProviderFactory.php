<?php

namespace Modules\HelpdeskIntegration\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskIntegration\Models\IntegrationProvider;

class IntegrationProviderFactory extends Factory
{
    protected $model = IntegrationProvider::class;

    public function definition(): array
    {
        return [
            'platform' => fake()->unique()->slug(2),
            'driver' => null,
            'label' => fake()->company(),
            'icon' => 'fas fa-plug',
            'color' => '#90bb13',
            'is_active' => true,
            'is_linkable' => true,
            'is_critical' => false,
            'search_types' => [
                ['value' => 'email', 'label' => 'Email'],
            ],
            'credentials' => null,
            'config' => null,
            'sort_order' => 0,
            'description' => fake()->optional()->sentence(),
        ];
    }
}
