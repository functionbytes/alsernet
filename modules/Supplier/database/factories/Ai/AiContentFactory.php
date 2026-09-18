<?php

namespace Modules\Supplier\Database\Factories\Ai;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Ai\AiContent;
use Modules\Supplier\Models\Supplier\Supplier;

class AiContentFactory extends Factory
{
    protected $model = AiContent::class;

    public function definition(): array
    {
        return [
            'uid' => (string) Str::ulid(),
            'supplier_id' => Supplier::factory(),
            'supplier_product_id' => null,
            'product_id' => null,
            'erp_reference' => fake()->optional()->bothify('ERP-####'),
            'model_id' => fake()->optional()->bothify('MOD-####'),
            'ean' => fake()->optional()->ean13(),
            'status' => fake()->randomElement([
                'pending_generation', 'generating', 'pending_validation',
                'in_review', 'needs_revision', 'validated', 'published',
                'rejected', 'error_insufficient_info', 'error_source_unavailable',
                'error_generation_failed',
            ]),
            'generated_name' => fake()->optional()->words(4, true),
            'short_description' => fake()->optional()->sentence(),
            'long_description' => fake()->optional()->paragraph(),
            'bullet_points' => null,
            'seo_title' => fake()->optional()->words(5, true),
            'seo_description' => fake()->optional()->sentence(),
            'seo_keywords' => fake()->optional()->words(5, true),
            'source_attributes' => null,
            'sources_used' => null,
            'prompt_id' => null,
            'generation_metadata' => null,
            'error_message' => null,
            'validated_by' => null,
            'validated_at' => null,
            'rejection_reason' => null,
            'published_at' => null,
            'synced_to_erp_at' => null,
            'content_hash' => null,
        ];
    }

    public function published(): static
    {
        return $this->state([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => fake()->randomElement([
                'error_insufficient_info',
                'error_source_unavailable',
                'error_generation_failed',
            ]),
        ]);
    }

    public function generating(): static
    {
        return $this->state(['status' => 'generating']);
    }
}
