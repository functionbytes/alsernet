<?php

namespace Modules\HelpdeskSocial\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Models\SocialComment;

class SocialCommentFactory extends Factory
{
    protected $model = SocialComment::class;

    public function definition(): array
    {
        $platform = $this->faker->randomElement(['facebook', 'instagram']);

        return [
            'social_account_id' => SocialAccount::factory(),
            'platform' => $platform,
            'external_comment_id' => $this->faker->uuid(),
            'external_post_id' => $this->faker->uuid(),
            'external_user_id' => $this->faker->numerify('##########'),
            'author_name' => $this->faker->name(),
            'author_username' => $this->faker->userName(),
            'body' => $this->faker->sentence(10),
            'status' => 'pending',
            'is_spam' => false,
            'is_mention' => false,
            'auto_replied' => false,
            'reply_type' => null,
            'posted_at' => now(),
        ];
    }
}
