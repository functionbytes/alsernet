<?php

namespace Modules\Seo\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Seo\Models\SeoMeta;

class SeoMetaFactory extends Factory
{
    protected $model = SeoMeta::class;

    public function definition(): array
    {
        return [
            'seoable_type' => 'App\\Models\\User',
            'seoable_id' => $this->faker->numberBetween(1, 999),
            'title' => $this->faker->sentence(6),
            'description' => $this->faker->sentence(15),
            'keywords' => implode(', ', $this->faker->words(5)),
            'target_keyword' => null,
            'og_title' => null,
            'og_description' => null,
            'og_image' => null,
            'locale' => 'es',
            'og_type' => 'website',
            'twitter_card' => 'summary',
            'twitter_title' => null,
            'twitter_description' => null,
            'twitter_image' => null,
            'canonical_url' => $this->faker->url(),
            'robots' => 'index,follow',
            'seo_score' => null,
            'seo_grade' => null,
            'seo_audited_at' => null,
            'gsc_clicks' => null,
            'gsc_impressions' => null,
            'gsc_position' => null,
            'gsc_updated_at' => null,
        ];
    }

    /**
     * State for a meta with a target keyword set.
     */
    public function withKeyword(string $keyword): static
    {
        return $this->state(['target_keyword' => $keyword]);
    }

    /**
     * State for a noindex meta.
     */
    public function noindex(): static
    {
        return $this->state(['robots' => 'noindex,nofollow']);
    }

    /**
     * State for an audited meta.
     */
    public function audited(): static
    {
        return $this->state([
            'seo_score' => $this->faker->numberBetween(50, 100),
            'seo_grade' => $this->faker->randomElement(['A', 'B', 'C']),
            'seo_audited_at' => now()->subHours($this->faker->numberBetween(1, 48)),
        ]);
    }
}
