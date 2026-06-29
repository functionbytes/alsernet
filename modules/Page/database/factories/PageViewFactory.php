<?php

namespace Modules\Page\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageView;

class PageViewFactory extends Factory
{
    protected $model = PageView::class;

    public function definition(): array
    {
        $viewedAt = $this->faker->dateTimeBetween('-30 days', 'now');

        return [
            'page_id' => Page::factory(),
            'session_id' => $this->faker->uuid(),
            'ip_hash' => hash('sha256', $this->faker->ipv4()),
            'locale' => $this->faker->randomElement(['es', 'en', 'pt']),
            'referrer' => $this->faker->optional()->url(),
            'referrer_domain' => $this->faker->optional()->domainName(),
            'device_type' => $this->faker->randomElement(['desktop', 'mobile', 'tablet']),
            'browser' => $this->faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge', 'Other']),
            'country_code' => $this->faker->optional()->randomElement(['ES', 'US', 'MX', 'AR', 'CO', 'PT']),
            'viewed_date' => $viewedAt->format('Y-m-d'),
            'viewed_at' => $viewedAt,
        ];
    }

    public function forPage(Page $page): static
    {
        return $this->state(['page_id' => $page->id]);
    }

    public function desktop(): static
    {
        return $this->state(['device_type' => 'desktop']);
    }

    public function mobile(): static
    {
        return $this->state(['device_type' => 'mobile']);
    }

    public function tablet(): static
    {
        return $this->state(['device_type' => 'tablet']);
    }

    public function withReferrer(string $url): static
    {
        return $this->state([
            'referrer' => $url,
            'referrer_domain' => parse_url($url, PHP_URL_HOST),
        ]);
    }

    public function withoutReferrer(): static
    {
        return $this->state([
            'referrer' => null,
            'referrer_domain' => null,
        ]);
    }

    public function onDate(string $date): static
    {
        return $this->state([
            'viewed_date' => $date,
            'viewed_at' => $date.' 12:00:00',
        ]);
    }
}
