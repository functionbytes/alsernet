<?php

namespace Modules\Attention\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Attention\Models\AttentionType;

class AttentionTypeFactory extends Factory
{
    protected $model = AttentionType::class;

    public function definition(): array
    {
        $types = [
            ['name' => 'Petición', 'code' => 'P', 'description' => 'Solicitud de información o servicios'],
            ['name' => 'Queja', 'code' => 'Q', 'description' => 'Manifestación de inconformidad'],
            ['name' => 'Reclamo', 'code' => 'R', 'description' => 'Solicitud de corrección'],
            ['name' => 'Sugerencia', 'code' => 'S', 'description' => 'Propuesta de mejora'],
            ['name' => 'Felicitación', 'code' => 'F', 'description' => 'Reconocimiento positivo'],
        ];

        $type = $this->faker->randomElement($types);

        return [
            'name' => $type['name'],
            'code' => $type['code'],
            'description' => $type['description'],
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the type is inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
