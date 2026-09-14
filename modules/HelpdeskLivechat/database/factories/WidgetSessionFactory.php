<?php

namespace Modules\HelpdeskLivechat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\HelpdeskLivechat\Models\WidgetSession;

class WidgetSessionFactory extends Factory
{
    protected $model = WidgetSession::class;

    public function definition(): array
    {
        return [
            'customer_id' => null,
            'session_token' => Str::random(64),
            'current_url' => 'https://example.com/page',
            'referrer' => null,
            'device' => [
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                'browser' => 'Chrome',
                'os' => 'macOS',
            ],
            'ip_address' => '8.8.8.8',
            'country_code' => 'US',
            'started_at' => now(),
            'last_activity_at' => now(),
        ];
    }

    /**
     * Associate the session with a customer.
     */
    public function forCustomer(int $customerId): static
    {
        return $this->state(['customer_id' => $customerId]);
    }

    /**
     * Simulate an inactive session (last activity over 5 minutes ago).
     */
    public function inactive(): static
    {
        return $this->state([
            'last_activity_at' => now()->subMinutes(10),
        ]);
    }
}
