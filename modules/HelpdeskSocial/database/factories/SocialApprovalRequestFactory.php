<?php

namespace Modules\HelpdeskSocial\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskSocial\Models\SocialApprovalRequest;
use Modules\HelpdeskSocial\Models\SocialComment;

class SocialApprovalRequestFactory extends Factory
{
    protected $model = SocialApprovalRequest::class;

    public function definition(): array
    {
        return [
            'social_comment_id' => SocialComment::factory(),
            'requested_by_user_id' => User::factory(),
            'approver_user_id' => User::factory(),
            'action_type' => $this->faker->randomElement(['reply', 'hide', 'escalate', 'delete']),
            'payload' => null,
            'status' => 'pending',
            'approver_note' => null,
            'responded_at' => null,
        ];
    }
}
