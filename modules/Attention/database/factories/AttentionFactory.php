<?php

namespace Modules\Attention\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Models\Attention;
use Modules\Attention\Models\AttentionCategory;
use Modules\Attention\Models\AttentionSede;
use Modules\Attention\Models\AttentionType;

class AttentionFactory extends Factory
{
    protected $model = Attention::class;

    public function definition(): array
    {
        return [
            'uid' => $this->faker->uuid(),
            'type_id' => AttentionType::factory(),
            'category_id' => AttentionCategory::factory(),
            'sede_id' => AttentionSede::factory(),
            'customer_firstname' => strtoupper($this->faker->firstName()),
            'customer_lastname' => strtoupper($this->faker->lastName()),
            'customer_email' => $this->faker->unique()->safeEmail(),
            'customer_cellphone' => $this->faker->numerify('3#########'),
            'customer_dni' => $this->faker->numerify('##########'),
            'customer_address' => $this->faker->address(),
            'is_anonymous' => false,
            'subject' => $this->faker->sentence(6),
            'description' => $this->faker->paragraph(3),
            'status' => AttentionStatus::RECEIVED,
        ];
    }

    /**
     * Indicate that the attention is anonymous
     */
    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_firstname' => null,
            'customer_lastname' => null,
            'customer_email' => null,
            'customer_cellphone' => null,
            'customer_dni' => null,
            'customer_address' => null,
            'is_anonymous' => true,
        ]);
    }

    /**
     * Indicate that the attention is in process
     */
    public function inProcess(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttentionStatus::IN_PROCESS,
        ]);
    }

    /**
     * Indicate that the attention is resolved
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttentionStatus::RESOLVED,
            'resolution' => $this->faker->paragraph(2),
            'resolved_at' => now(),
        ]);
    }

    /**
     * Indicate that the attention is closed
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttentionStatus::CLOSED,
            'closed_at' => now(),
        ]);
    }
}
