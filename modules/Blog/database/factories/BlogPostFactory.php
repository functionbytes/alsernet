<?php

namespace Modules\Blog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Blog\Models\BlogPost;

class BlogPostFactory extends Factory
{
    protected $model = BlogPost::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['draft', 'published', 'pending']);

        return [
            'title' => $this->faker->sentence(5),
            'slug' => $this->faker->unique()->slug(4),
            'description' => $this->faker->sentence(15),
            'content' => $this->faker->paragraphs(3, true),
            'status' => $status,
            'is_featured' => 0,
            'image' => null,
            'format_type' => null,
            'views' => $this->faker->numberBetween(0, 1000),
            'user_id' => null,
            'seo_title' => null,
            'seo_description' => null,
            'seo_keywords' => null,
            'published_at' => $status === 'published' ? $this->faker->dateTimeBetween('-1 year', 'now') : null,
        ];
    }

    /**
     * Indicate that the post is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * Indicate that the post is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the post is pending review.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the post is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => 1,
        ]);
    }

    /**
     * Indicate that the post has SEO data.
     */
    public function withSeo(): static
    {
        return $this->state(fn (array $attributes) => [
            'seo_title' => $this->faker->sentence(6),
            'seo_description' => $this->faker->sentence(20),
            'seo_keywords' => implode(', ', $this->faker->words(5)),
        ]);
    }
}
