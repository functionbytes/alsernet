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
            ['name' => 'Atención al Cliente', 'code' => 'AC'],
            ['name' => 'Recursos Humanos', 'code' => 'RH'],
            ['name' => 'Administración', 'code' => 'AD'],
            ['name' => 'Soporte Técnico', 'code' => 'ST'],
            ['name' => 'Calidad', 'code' => 'CA'],
            ['name' => 'Finanzas', 'code' => 'FI'],
        ];

        $dept = $this->faker->randomElement($departments);

        return [
            'name' => $dept['name'],
            'code' => $dept['code'],
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
