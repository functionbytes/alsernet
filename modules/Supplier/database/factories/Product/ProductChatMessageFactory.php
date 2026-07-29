<?php

namespace Modules\Supplier\Database\Factories\Product;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Supplier\Models\Product\ProductChat;
use Modules\Supplier\Models\Product\ProductChatMessage;

class ProductChatMessageFactory extends Factory
{
    protected $model = ProductChatMessage::class;

    public function definition(): array
    {
        $role = fake()->randomElement(['user', 'assistant']);
        $inputTokens = $role === 'assistant' ? fake()->numberBetween(50, 4000) : 0;
        $outputTokens = $role === 'assistant' ? fake()->numberBetween(50, 2000) : 0;

        return [
            'chat_id' => ProductChat::factory(),
            'role' => $role,
            'content' => fake()->paragraph(),
            'sources' => null,
            'web_search_used' => false,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'cost' => round(($inputTokens * 0.00000015) + ($outputTokens * 0.0000006), 6),
            'model' => $role === 'assistant' ? 'gpt-4o-mini' : null,
            'latency_ms' => $role === 'assistant' ? fake()->numberBetween(200, 5000) : null,
        ];
    }

    public function user(): static
    {
        return $this->state([
            'role' => 'user',
            'input_tokens' => 0,
            'output_tokens' => 0,
            'cost' => 0,
            'model' => null,
            'latency_ms' => null,
        ]);
    }

    public function assistant(): static
    {
        return $this->state(['role' => 'assistant']);
    }

    public function system(): static
    {
        return $this->state([
            'role' => 'system',
            'input_tokens' => 0,
            'output_tokens' => 0,
            'cost' => 0,
            'model' => null,
            'latency_ms' => null,
        ]);
    }
}
