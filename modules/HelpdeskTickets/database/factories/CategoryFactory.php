<?php

namespace Modules\HelpdeskTickets\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskTickets\Models\TicketCategory;

/**
 * Factory for TicketCategory model.
 */
class CategoryFactory extends Factory
{
    protected $model = TicketCategory::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Soporte técnico', 'Facturación', 'Consultas', 'Quejas', 'Ventas']).'-'.fake()->numberBetween(1, 99),
            'slug' => fake()->unique()->slug(2),
            'description' => fake()->optional()->sentence(),
            'icon' => fake()->randomElement(['fas fa-headset', 'fas fa-receipt', 'fas fa-question-circle', 'fas fa-exclamation-circle']),
            'color' => fake()->randomElement(['primary', 'success', 'warning', 'danger', 'info']),
            'order' => fake()->numberBetween(1, 10),
            'default_sla_policy_id' => null,
            'custom_form_fields' => null,
            'required_fields' => null,
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }

    public function withCustomFields(): static
    {
        return $this->state([
            'custom_form_fields' => [
                ['name' => 'order_number', 'label' => 'Número de pedido', 'type' => 'text', 'required' => true],
            ],
            'required_fields' => ['order_number'],
        ]);
    }
}
