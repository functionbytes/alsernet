<?php

namespace Modules\Blog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Blog\Models\BlogCategory;

class BlogCategoryFactory extends Factory
{
    protected $model = BlogCategory::class;

    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => $this->faker->unique()->slug(3),
            'description' => $this->faker->sentence(12),
            'parent_id' => 0,
            'status' => 'published',
            'icon' => null,
            'order' => $this->faker->numberBetween(0, 20),
            'is_featured' => 0,
            'is_default' => 0,
            'user_id' => null,
        ];
    }

    /**
     * Indicate that the category is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Indicate that the category is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => 1,
        ]);
    }

    /**
     * Indicate that the category is the default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => 1,
        ]);
    }

    /**
     * Indicate that the category is a child of another.
     */
    public function child(int $parentId): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parentId,
        ]);
    }
}
