<?php

namespace Modules\Engagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Engagement\Models\AuditLog;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'inbox_id' => 1,
            'user_id' => 1,
            'action' => $this->faker->randomElement(['created', 'updated', 'deleted']),
            'entity_type' => $this->faker->randomElement(['trigger', 'segment', 'ab_test']),
            'entity_id' => $this->faker->randomNumber(),
            'metadata' => ['field' => 'name'],
        ];
    }
}
