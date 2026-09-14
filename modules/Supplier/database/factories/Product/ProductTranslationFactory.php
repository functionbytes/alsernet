<?php

namespace Modules\Supplier\Database\Factories\Product;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Product\ProductTranslation;

class ProductTranslationFactory extends Factory
{
    protected $model = ProductTranslation::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'lang_id' => fn () => DB::table('langs')->inRandomOrder()->value('id') ?? 1,
            'description' => fake()->paragraphs(2, true),
            'generated_by_ai' => true,
            'prompt_used' => fake()->optional()->sentence(),
            'ai_model' => fake()->randomElement(['gpt-4o', 'gpt-4o-mini', 'claude-3-5-sonnet']),
        ];
    }

    public function manual(): static
    {
        return $this->state([
            'generated_by_ai' => false,
            'prompt_used' => null,
            'ai_model' => null,
        ]);
    }

    public function forLang(int $langId): static
    {
        return $this->state(['lang_id' => $langId]);
    }
}
