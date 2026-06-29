<?php

namespace Modules\HelpdeskTickets\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskTickets\Models\TicketCategory;

class TicketCategoryFactory extends Factory
{
    protected $model = TicketCategory::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Soporte técnico', 'Facturación', 'Ventas', 'General', 'Reclamos',
                'Garantías', 'Instalación', 'Configuración', 'Consultas', 'Urgente',
            ]).'-'.fake()->numberBetween(1, 99),
            'slug' => fake()->unique()->slug(2),
            'description' => fake()->optional()->sentence(),
            'icon' => 'fas fa-tag',
            'color' => fake()->hexColor(),
            'order' => null,
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
                ['name' => 'serial_number', 'type' => 'text', 'label' => 'Número de serie'],
            ],
            'required_fields' => ['serial_number'],
        ]);
    }
}
