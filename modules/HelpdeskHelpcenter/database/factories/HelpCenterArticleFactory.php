<?php

namespace Modules\HelpdeskHelpcenter\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;

class HelpCenterArticleFactory extends Factory
{
    protected $model = HelpCenterArticle::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(6);

        return [
            'category_id' => null,
            'title' => $title,
            'slug' => Str::slug($title).'-'.uniqid(),
            'body' => $this->faker->paragraphs(3, true),
            'content' => null,
            'description' => $this->faker->sentence(),
            'excerpt' => $this->faker->sentence(),
            'position' => $this->faker->numberBetween(1, 100),
            'order' => $this->faker->numberBetween(1, 100),
            'views_count' => 0,
            'active' => true,
            'draft' => false,
            'hide_from_structure' => false,
            'is_published' => true,
            'author_id' => null,
            'published_at' => now()->subDay(),
            'meta_description' => $this->faker->sentence(),
        ];
    }

    public function draft(): static
    {
        return $this->state([
            'draft' => true,
            'is_published' => false,
        ]);
    }

    public function unpublished(): static
    {
        return $this->state([
            'is_published' => false,
        ]);
    }

    public function published(): static
    {
        return $this->state([
            'is_published' => true,
            'draft' => false,
            'published_at' => now()->subHour(),
        ]);
    }
}
