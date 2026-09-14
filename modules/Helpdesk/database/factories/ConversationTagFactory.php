<?php

namespace Modules\Helpdesk\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Helpdesk\Models\ConversationTag;

class ConversationTagFactory extends Factory
{
    protected $model = ConversationTag::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'urgente', 'VIP', 'seguimiento', 'escalado', 'nuevo cliente',
                'recurrente', 'pendiente pago', 'técnico', 'comercial', 'postventa',
            ]).'-'.fake()->numberBetween(1, 99),
            'slug' => fake()->unique()->slug(2),
            'color' => fake()->hexColor(),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
