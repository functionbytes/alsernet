<?php

namespace Modules\Blog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Blog\Models\BlogTag;

class BlogTagFactory extends Factory
{
    protected $model = BlogTag::class;

    public function definition(): array
    {
        $word = $this->faker->unique()->word();

        return [
            'name' => ucfirst($word),
            'slug' => $this->faker->unique()->slug(2),
            'description' => $this->faker->optional(0.6)->sentence(8),
            'status' => 'published',
            'user_id' => null,
        ];
    }

    /**
     * Indicate that the tag is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }
}
