<?php

namespace Modules\HelpdeskSocial\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskSocial\Models\SocialAccount;

class SocialAccountFactory extends Factory
{
    protected $model = SocialAccount::class;

    public function definition(): array
    {
        $platform = $this->faker->randomElement(['facebook', 'instagram', 'whatsapp']);

        return [
            'name' => $this->faker->company(),
            'platform' => $platform,
            'account_type' => 'page',
            'external_id' => $this->faker->numerify('##########'),
            'username' => $this->faker->userName(),
            'profile_url' => $this->faker->url(),
            'is_active' => true,
            'comments_enabled' => true,
            'messages_enabled' => true,
            'auto_reply_enabled' => false,
            'connected_by_user_id' => 1,
        ];
    }
}
