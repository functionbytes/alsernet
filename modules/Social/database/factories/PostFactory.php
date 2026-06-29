<?php

namespace Modules\Social\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Social\Enums\PostStatus;
use Modules\Social\Enums\PostType;
use Modules\Social\Models\Post;
use Modules\Social\Models\SocialAccount;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'account_id' => 1,
            'social_account_id' => SocialAccount::factory(),
            'campaign_id' => null,
            'type' => fake()->randomElement(PostType::cases()),
            'status' => fake()->randomElement(PostStatus::cases()),
            'content' => fake()->paragraph(3),
            'media_urls' => [],
            'scheduled_at' => null,
            'published_at' => null,
            'external_id' => null,
            'external_url' => null,
            'reach' => 0,
            'impressions' => 0,
            'likes_count' => 0,
            'comments_count' => 0,
            'shares_count' => 0,
            'clicks' => 0,
            'created_by' => 1,
        ];
    }

    /**
     * Draft post state
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::DRAFT,
            'scheduled_at' => null,
            'published_at' => null,
        ]);
    }

    /**
     * Scheduled post state
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::SCHEDULED,
            'scheduled_at' => fake()->dateTimeBetween('now', '+7 days'),
            'published_at' => null,
        ]);
    }

    /**
     * Published post state with metrics
     */
    public function published(): static
    {
        $publishedAt = fake()->dateTimeBetween('-30 days', 'now');

        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::PUBLISHED,
            'scheduled_at' => null,
            'published_at' => $publishedAt,
            'external_id' => fake()->numerify('##########_##########'),
            'external_url' => fake()->url(),
            'reach' => fake()->numberBetween(100, 10000),
            'impressions' => fake()->numberBetween(150, 15000),
            'likes_count' => fake()->numberBetween(5, 500),
            'comments_count' => fake()->numberBetween(0, 50),
            'shares_count' => fake()->numberBetween(0, 30),
            'clicks' => fake()->numberBetween(10, 200),
        ]);
    }

    /**
     * Failed post state
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::FAILED,
            'error_message' => 'Failed to publish: '.fake()->sentence(),
        ]);
    }

    /**
     * Text post type
     */
    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PostType::TEXT,
            'content' => fake()->paragraph(2),
            'media_urls' => [],
        ]);
    }

    /**
     * Image post type
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PostType::IMAGE,
            'content' => fake()->sentence(),
            'media_urls' => [
                fake()->imageUrl(1200, 630, 'business'),
            ],
        ]);
    }

    /**
     * Video post type
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PostType::VIDEO,
            'content' => fake()->sentence(),
            'media_urls' => [
                'https://example.com/videos/'.fake()->uuid().'.mp4',
            ],
        ]);
    }

    /**
     * Link post type
     */
    public function link(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PostType::LINK,
            'content' => fake()->sentence(),
            'link_url' => fake()->url(),
            'link_title' => fake()->sentence(6),
            'link_description' => fake()->sentence(12),
        ]);
    }

    /**
     * Carousel post type
     */
    public function carousel(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PostType::CAROUSEL,
            'content' => fake()->sentence(),
            'media_urls' => [
                fake()->imageUrl(1200, 630, 'business', true),
                fake()->imageUrl(1200, 630, 'business', true),
                fake()->imageUrl(1200, 630, 'business', true),
            ],
        ]);
    }

    /**
     * High performing post
     */
    public function highPerformance(): static
    {
        return $this->published()->state(fn (array $attributes) => [
            'reach' => fake()->numberBetween(5000, 50000),
            'impressions' => fake()->numberBetween(10000, 80000),
            'likes_count' => fake()->numberBetween(200, 2000),
            'comments_count' => fake()->numberBetween(50, 300),
            'shares_count' => fake()->numberBetween(30, 200),
            'clicks' => fake()->numberBetween(100, 1000),
        ]);
    }
}
