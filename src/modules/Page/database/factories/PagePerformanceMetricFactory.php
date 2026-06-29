<?php

namespace Modules\Page\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Page\Models\Page;
use Modules\Page\Models\PagePerformanceMetric;

class PagePerformanceMetricFactory extends Factory
{
    protected $model = PagePerformanceMetric::class;

    public function definition(): array
    {
        return [
            'page_id' => Page::factory(),
            'strategy' => $this->faker->randomElement(['mobile', 'desktop']),
            'performance_score' => $this->faker->randomFloat(1, 0, 100),
            'accessibility_score' => $this->faker->randomFloat(1, 50, 100),
            'seo_score' => $this->faker->randomFloat(1, 50, 100),
            'best_practices_score' => $this->faker->randomFloat(1, 50, 100),
            'lcp' => $this->faker->numberBetween(500, 8000),
            'fid' => $this->faker->numberBetween(10, 500),
            'cls_score' => $this->faker->numberBetween(0, 500),
            'fcp' => $this->faker->numberBetween(300, 5000),
            'ttfb' => $this->faker->numberBetween(50, 2000),
            'tbt' => $this->faker->numberBetween(0, 2000),
            'speed_index' => $this->faker->numberBetween(500, 10000),
            'opportunities' => [],
            'diagnostics' => null,
            'page_url' => $this->faker->url(),
        ];
    }

    public function forPage(Page $page): static
    {
        return $this->state(['page_id' => $page->id]);
    }

    public function mobile(): static
    {
        return $this->state(['strategy' => 'mobile']);
    }

    public function desktop(): static
    {
        return $this->state(['strategy' => 'desktop']);
    }

    public function good(): static
    {
        return $this->state([
            'performance_score' => $this->faker->randomFloat(1, 90, 100),
        ]);
    }

    public function poor(): static
    {
        return $this->state([
            'performance_score' => $this->faker->randomFloat(1, 0, 49),
        ]);
    }
}
