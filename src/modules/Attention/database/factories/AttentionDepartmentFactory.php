<?php

namespace Modules\Attention\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Attention\Models\AttentionDepartment;

class AttentionDepartmentFactory extends Factory
{
    protected $model = AttentionDepartment::class;

    public function definition(): array
    {
        $departments = [
            'Atención al Cliente',
            'Recursos Humanos',
            'Administración',
            'Soporte Técnico',
            'Calidad',
            'Finanzas',
        ];

        return [
            'name' => $this->faker->unique()->randomElement($departments),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the department is inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
