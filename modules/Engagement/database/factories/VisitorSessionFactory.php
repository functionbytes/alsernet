<?php

namespace Modules\Engagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Engagement\Models\VisitorSession;

class VisitorSessionFactory extends Factory
{
    protected $model = VisitorSession::class;

    public function definition(): array
    {
        return [
            'inbox_id' => 1,
            'session_token' => $this->faker->uuid,
            'visitor_id' => $this->faker->uuid,
            'current_url' => $this->faker->url,
            'referrer' => $this->faker->url,
            'device' => [
                'userAgent' => $this->faker->userAgent,
                'language' => $this->faker->languageCode,
                'timezone' => $this->faker->timezone,
                'viewport' => '1920x1080',
            ],
            'ip_address' => $this->faker->ipv4,
            'country_code' => $this->faker->countryCode,
            'started_at' => now(),
            'last_activity_at' => now(),
        ];
    }
}
