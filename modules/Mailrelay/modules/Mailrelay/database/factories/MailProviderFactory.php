<?php

namespace Modules\Mailrelay\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Mailrelay\Entities\MailProvider;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Mailrelay\Entities\MailProvider>
 */
class MailProviderFactory extends Factory
{
    protected $model = MailProvider::class;

    public function definition(): array
    {
        $drivers = ['mailrelay', 'mailtrap', 'sendgrid', 'aws_ses', 'postmark'];
        $driver = $this->faker->randomElement($drivers);

        return [
            'name' => $this->faker->company.' Mail Service',
            'driver' => $driver,
            'credentials' => $this->getCredentialsForDriver($driver),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'is_default' => false,
            'priority' => $this->faker->numberBetween(0, 100),
            'limits' => [
                'max_per_day' => $this->faker->numberBetween(1000, 100000),
                'max_per_hour' => $this->faker->numberBetween(100, 10000),
            ],
            'metadata' => [
                'description' => $this->faker->sentence(),
                'environment' => $this->faker->randomElement(['production', 'development', 'staging']),
            ],
            'connection_ok' => $this->faker->boolean(70),
            'last_tested_at' => $this->faker->optional()->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Get appropriate credentials structure for each driver
     */
    protected function getCredentialsForDriver(string $driver): array
    {
        return match ($driver) {
            'mailrelay' => [
                'api_url' => 'https://api.mailrelay.com/v1',
                'api_key' => 'test_'.bin2hex(random_bytes(16)),
                'from_email' => $this->faker->safeEmail(),
                'from_name' => $this->faker->company(),
                'reply_to' => $this->faker->safeEmail(),
            ],
            'mailtrap' => [
                'api_token' => 'test_'.bin2hex(random_bytes(16)),
                'from_email' => $this->faker->safeEmail(),
                'from_name' => $this->faker->company(),
                'reply_to' => $this->faker->safeEmail(),
            ],
            'sendgrid' => [
                'api_key' => 'SG.'.bin2hex(random_bytes(32)),
                'from_email' => $this->faker->safeEmail(),
                'from_name' => $this->faker->company(),
                'reply_to' => $this->faker->safeEmail(),
            ],
            'aws_ses' => [
                'access_key' => 'AKIA'.strtoupper(bin2hex(random_bytes(8))),
                'secret_key' => bin2hex(random_bytes(20)),
                'region' => $this->faker->randomElement(['us-east-1', 'us-west-2', 'eu-west-1']),
                'from_email' => $this->faker->safeEmail(),
                'from_name' => $this->faker->company(),
                'reply_to' => $this->faker->safeEmail(),
                'configuration_set' => 'test-emails',
            ],
            'postmark' => [
                'server_token' => bin2hex(random_bytes(16)),
                'from_email' => $this->faker->safeEmail(),
                'from_name' => $this->faker->company(),
                'reply_to' => $this->faker->safeEmail(),
                'message_stream' => 'outbound',
            ],
            default => [
                'api_key' => 'test_'.bin2hex(random_bytes(16)),
                'from_email' => $this->faker->safeEmail(),
                'from_name' => $this->faker->company(),
            ],
        };
    }

    /**
     * Indicate that the provider is the default provider.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
            'is_active' => true,
            'priority' => 100,
        ]);
    }

    /**
     * Indicate that the provider is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the provider is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the provider has a working connection.
     */
    public function connected(): static
    {
        return $this->state(fn (array $attributes) => [
            'connection_ok' => true,
            'last_tested_at' => now(),
        ]);
    }

    /**
     * Indicate that the provider has a failed connection.
     */
    public function disconnected(): static
    {
        return $this->state(fn (array $attributes) => [
            'connection_ok' => false,
            'last_tested_at' => now(),
        ]);
    }
}
