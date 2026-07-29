<?php

namespace Modules\Supplier\Database\Factories\Product;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Product\ProductChat;

class ProductChatFactory extends Factory
{
    protected $model = ProductChat::class;

    public function definition(): array
    {
        return [
            'uid' => (string) Str::ulid(),
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'model' => fake()->randomElement(['gpt-4o-mini', 'gpt-4o', 'claude-3-5-sonnet']),
            'web_search_enabled' => false,
            'prompt_uid' => null,
            'total_cost' => 0,
            'total_tokens' => 0,
            'messages_count' => 0,
            'saved_content_id' => null,
        ];
    }

    public function webSearchEnabled(): static
    {
        return $this->state(['web_search_enabled' => true]);
    }

    public function withUsage(): static
    {
        return $this->state([
            'total_cost' => fake()->randomFloat(6, 0.0001, 0.5),
            'total_tokens' => fake()->numberBetween(100, 20000),
            'messages_count' => fake()->numberBetween(2, 12),
        ]);
    }
}
