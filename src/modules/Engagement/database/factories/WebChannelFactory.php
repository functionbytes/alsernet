<?php

namespace Modules\Engagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Engagement\Models\WebChannel;

class WebChannelFactory extends Factory
{
    protected $model = WebChannel::class;

    public function definition(): array
    {
        return [
            'inbox_id' => 1,
            'name' => $this->faker->domainWord(),
            'website_token' => $this->faker->sha256(),
            'domain' => $this->faker->domainName(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
