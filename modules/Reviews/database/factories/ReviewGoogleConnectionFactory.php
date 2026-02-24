<?php

namespace Modules\Reviews\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Reviews\Models\ReviewGoogleConnection;

class ReviewGoogleConnectionFactory extends Factory
{
    protected $model = ReviewGoogleConnection::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company().' - Google Business',
            'google_account_id' => 'accounts/'.fake()->numerify('##############'),
            'google_email' => fake()->unique()->companyEmail(),
            'access_token' => fake()->sha256(),
            'refresh_token' => fake()->sha256(),
            'token_expires_at' => now()->addDays(30),
            'scopes' => ['https://www.googleapis.com/auth/business.manage'],
            'status' => 'active',
            'last_error' => null,
            'connected_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'revoked_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status' => 'pending',
            'connected_at' => null,
            'token_expires_at' => null,
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'status' => 'expired',
            'token_expires_at' => now()->subHour(),
        ]);
    }

    public function revoked(): static
    {
        return $this->state([
            'status' => 'revoked',
            'revoked_at' => now()->subDays(rand(1, 10)),
        ]);
    }

    public function error(): static
    {
        return $this->state([
            'status' => 'error',
            'last_error' => 'Authentication failed: Invalid credentials',
        ]);
    }
}
