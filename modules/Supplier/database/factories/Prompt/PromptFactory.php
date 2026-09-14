<?php

namespace Modules\Supplier\Database\Factories\Prompt;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Prompt\Prompt;
use Modules\Supplier\Models\Supplier\Supplier;

class PromptFactory extends Factory
{
    protected $model = Prompt::class;

    public function definition(): array
    {
        return [
            'uid' => (string) Str::ulid(),
            'supplier_id' => Supplier::factory(),
            'category_id' => null,
            'source_id' => null,
            'scope' => fake()->randomElement(['global', 'supplier', 'category', 'supplier_category', 'source']),
            'label' => fake()->words(3, true),
            'content_type' => fake()->randomElement([
                'description', 'short_description', 'title', 'seo_title',
                'seo_description', 'seo_keywords', 'metadata', 'features',
                'specifications', 'benefits',
            ]),
            'prompt_template' => fake()->paragraph(),
            'output_language' => fake()->randomElement(['es-ES', 'en-GB', 'fr-FR', 'de-DE']),
            'tone' => fake()->randomElement(['technical', 'commercial', 'formal', 'casual']),
            'priority' => fake()->numberBetween(1, 10),
            'seo_focus' => fake()->boolean(),
            'enable_web_search' => fake()->boolean(20),
            'required_sections' => null,
            'version' => 1,
            'is_default' => false,
            'is_active' => true,
            'is_template' => false,
            'template_category' => null,
            'cloned_from_template_id' => null,
        ];
    }

    public function global(): static
    {
        return $this->state([
            'scope' => 'global',
            'supplier_id' => null,
            'category_id' => null,
        ]);
    }

    public function template(): static
    {
        return $this->state([
            'is_template' => true,
            'template_category' => fake()->randomElement(['general', 'seo', 'descriptions', 'titles']),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}
