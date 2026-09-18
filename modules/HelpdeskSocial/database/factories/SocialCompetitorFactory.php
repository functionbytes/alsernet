<?php

namespace Modules\HelpdeskSocial\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Models\SocialCompetitor;

class SocialCompetitorFactory extends Factory
{
    protected $model = SocialCompetitor::class;

    public function definition(): array
    {
        return [
            'social_account_id' => SocialAccount::factory(),
            'name' => $this->faker->company(),
            'platform' => $this->faker->randomElement(['facebook', 'instagram', 'tiktok', 'x', 'linkedin']),
            'external_id' => $this->faker->numerify('##########'),
            'username' => $this->faker->userName(),
            'profile_url' => $this->faker->url(),
            'is_active' => true,
        ];
    }
}
