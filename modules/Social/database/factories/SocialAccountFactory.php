<?php

namespace Modules\Social\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Social\Enums\SocialNetwork;
use Modules\Social\Models\SocialAccount;

class SocialAccountFactory extends Factory
{
    protected $model = SocialAccount::class;

    public function definition(): array
    {
        $network = fake()->randomElement(SocialNetwork::cases());

        return [
            'account_id' => 1,
            'network' => $network,
            'network_id' => fake()->numerify('##########'),
            'name' => $this->getNetworkName($network),
            'username' => fake()->userName(),
            'avatar' => fake()->imageUrl(200, 200, 'people'),
            'access_token' => encrypt('fake_access_token_'.fake()->uuid()),
            'refresh_token' => encrypt('fake_refresh_token_'.fake()->uuid()),
            'token_expires_at' => now()->addDays(60),
            'status' => fake()->randomElement([1, 2]), // 1=active, 2=inactive
            'follower_count' => fake()->numberBetween(100, 100000),
            'last_sync_at' => now(),
        ];
    }

    /**
     * Active account state
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 1,
        ]);
    }

    /**
     * Inactive account state
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 2,
        ]);
    }

    /**
     * Facebook account state
     */
    public function facebook(): static
    {
        return $this->state(fn (array $attributes) => [
            'network' => SocialNetwork::FACEBOOK,
            'name' => 'Facebook Page - '.fake()->company(),
            'username' => fake()->userName(),
        ]);
    }

    /**
     * Instagram account state
     */
    public function instagram(): static
    {
        return $this->state(fn (array $attributes) => [
            'network' => SocialNetwork::INSTAGRAM,
            'name' => '@'.fake()->userName(),
            'username' => fake()->userName(),
        ]);
    }

    /**
     * Twitter account state
     */
    public function twitter(): static
    {
        return $this->state(fn (array $attributes) => [
            'network' => SocialNetwork::TWITTER,
            'name' => '@'.fake()->userName(),
            'username' => fake()->userName(),
        ]);
    }

    /**
     * LinkedIn account state
     */
    public function linkedin(): static
    {
        return $this->state(fn (array $attributes) => [
            'network' => SocialNetwork::LINKEDIN,
            'name' => fake()->company(),
            'username' => fake()->slug(),
        ]);
    }

    /**
     * Get appropriate name based on network
     */
    protected function getNetworkName(SocialNetwork $network): string
    {
        return match ($network) {
            SocialNetwork::FACEBOOK => 'Facebook Page - '.fake()->company(),
            SocialNetwork::INSTAGRAM => '@'.fake()->userName(),
            SocialNetwork::TWITTER => '@'.fake()->userName(),
            SocialNetwork::LINKEDIN => fake()->company(),
        };
    }
}
