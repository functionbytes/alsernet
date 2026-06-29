<?php

namespace Modules\Seo\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Seo\Models\SeoRedirect;

class SeoRedirectFactory extends Factory
{
    protected $model = SeoRedirect::class;

    public function definition(): array
    {
        return [
            'source_path' => '/'.$this->faker->unique()->slug(),
            'target_path' => '/'.$this->faker->slug(),
            'status_code' => $this->faker->randomElement([301, 302]),
            'hits_count' => 0,
            'is_active' => true,
            'is_regex' => false,
            'is_wildcard' => false,
            'active_from' => null,
            'active_until' => null,
        ];
    }

    /**
     * State for an inactive redirect.
     */
    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    /**
     * State for a regex redirect.
     */
    public function regex(): static
    {
        return $this->state(['is_regex' => true, 'is_wildcard' => false]);
    }

    /**
     * State for a wildcard redirect.
     */
    public function wildcard(): static
    {
        return $this->state(['is_wildcard' => true, 'is_regex' => false]);
    }

    /**
     * State for an expired redirect.
     */
    public function expired(): static
    {
        return $this->state(['active_until' => now()->subDay()]);
    }
}
