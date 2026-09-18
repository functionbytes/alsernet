<?php

namespace Modules\Helpdesk\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Helpdesk\Models\ConversationView;

class ConversationViewFactory extends Factory
{
    protected $model = ConversationView::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'filters' => [],
            'sort_by' => null,
            'sort_direction' => 'desc',
            'user_id' => null,
            'is_public' => false,
            'is_default' => false,
            'is_system' => false,
            'order' => fake()->numberBetween(1, 100),
        ];
    }

    public function public(): static
    {
        return $this->state(['is_public' => true]);
    }

    public function private(int $userId): static
    {
        return $this->state([
            'is_public' => false,
            'user_id' => $userId,
        ]);
    }

    public function system(): static
    {
        return $this->state(['is_system' => true]);
    }

    public function withSort(string $sortBy = 'created_at', string $direction = 'desc'): static
    {
        return $this->state([
            'sort_by' => $sortBy,
            'sort_direction' => $direction,
        ]);
    }
}
