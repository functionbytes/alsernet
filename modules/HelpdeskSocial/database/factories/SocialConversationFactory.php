<?php

namespace Modules\HelpdeskSocial\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Models\SocialConversation;

class SocialConversationFactory extends Factory
{
    protected $model = SocialConversation::class;

    public function definition(): array
    {
        $platform = $this->faker->randomElement(['facebook', 'instagram', 'whatsapp']);

        return [
            'social_account_id' => SocialAccount::factory(),
            'platform' => $platform,
            'external_conversation_id' => $this->faker->uuid(),
            'external_post_id' => $this->faker->uuid(),
            'participant_external_id' => $this->faker->numerify('##########'),
            'participant_name' => $this->faker->name(),
            'status' => $this->faker->randomElement(['open', 'closed', 'pending', 'spam']),
            'message_count' => $this->faker->numberBetween(1, 50),
            'last_message_at' => now(),
        ];
    }
}
