<?php

namespace Modules\Engagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Engagement\Models\MobileDevice;

class MobileDeviceFactory extends Factory
{
    protected $model = MobileDevice::class;

    public function definition(): array
    {
        return [
            'inbox_id' => 1,
            'device_token' => $this->faker->sha256(),
            'platform' => $this->faker->randomElement(['ios', 'android']),
            'os_version' => $this->faker->randomElement(['16.0', '17.0', '14.0', '15.0']),
            'app_version' => '1.0.0',
            'locale' => $this->faker->locale(),
            'metadata' => ['model' => $this->faker->word()],
            'last_seen_at' => now(),
            'push_enabled' => true,
        ];
    }

    public function ios(): static
    {
        return $this->state(['platform' => 'ios']);
    }

    public function android(): static
    {
        return $this->state(['platform' => 'android']);
    }

    public function disabled(): static
    {
        return $this->state(['push_enabled' => false]);
    }
}
