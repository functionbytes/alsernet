<?php

namespace Modules\Supplier\Database\Factories\Ai;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Ai\AiCost;

class AiCostFactory extends Factory
{
    protected $model = AiCost::class;

    public function definition(): array
    {
        $inputTokens = $this->faker->numberBetween(50, 5000);
        $outputTokens = $this->faker->numberBetween(50, 3000);
        $model = $this->faker->randomElement(['gpt-4o-mini', 'gpt-4o', 'claude-3-5-haiku', 'claude-3-5-sonnet']);

        return [
            'supplier_id' => null,
            'content_id' => null,
            'batch_id' => null,
            'model' => $model,
            'operation_type' => $this->faker->randomElement(['generation', 'translation', 'chat']),
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'input_cost' => round($inputTokens * 0.00000015, 6),
            'output_cost' => round($outputTokens * 0.0000006, 6),
            'request_id' => 'req_'.$this->faker->uuid(),
            'latency_ms' => $this->faker->numberBetween(100, 5000),
        ];
    }
}
