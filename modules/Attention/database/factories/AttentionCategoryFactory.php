<?php

namespace Modules\Attention\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Attention\Models\AttentionCategory;

class AttentionCategoryFactory extends Factory
{
    protected $model = AttentionCategory::class;

    public function definition(): array
    {
        $categories = [
            'Servicios Generales',
            'Atención al Cliente',
            'Facturación',
            'Soporte Técnico',
            'Recursos Humanos',
            'Administración',
            'Calidad',
            'Seguridad',
        ];

        return [
            'name' => $this->faker->unique()->randomElement($categories),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the category is inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
