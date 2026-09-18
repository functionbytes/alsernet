<?php

namespace Modules\HelpdeskSocial\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Models\SocialCommentNote;

class SocialCommentNoteFactory extends Factory
{
    protected $model = SocialCommentNote::class;

    public function definition(): array
    {
        return [
            'social_comment_id' => SocialComment::factory(),
            'user_id' => User::factory(),
            'body' => $this->faker->sentence(10),
            'type' => $this->faker->randomElement(['internal', 'escalation', 'info']),
        ];
    }
}
