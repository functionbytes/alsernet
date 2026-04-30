<?php

namespace Modules\Chat\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Chat\Models\Helpcenters\HelpcenterArticle;

class HelpcenterArticleFactory extends Factory
{
    protected $model = HelpcenterArticle::class;

    public function definition(): array
    {
        $title = fake()->sentence();

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 10000),
            'body' => fake()->paragraphs(3, true),
            'description' => fake()->sentence(),
            'meta_description' => fake()->sentence(),
            'draft' => false,
            'hide_from_structure' => false,
            'views' => 0,
            'was_helpful' => 0,
            'position' => fake()->numberBetween(1, 100),
            'author_id' => User::factory(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'draft' => true,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'draft' => false,
        ]);
    }

    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'views' => fake()->numberBetween(100, 1000),
            'was_helpful' => fake()->numberBetween(50, 500),
        ]);
    }
}
