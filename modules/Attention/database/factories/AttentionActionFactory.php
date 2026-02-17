<?php

namespace Modules\Attention\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Attention\Models\Attention;
use Modules\Attention\Models\AttentionAction;

class AttentionActionFactory extends Factory
{
    protected $model = AttentionAction::class;

    public function definition(): array
    {
        $actions = [
            'created',
            'status_changed',
            'assigned',
            'department_assigned',
            'note_added',
            'attachment_added',
            'email_sent',
            'resolved',
            'closed',
        ];

        return [
            'attention_id' => Attention::factory(),
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'action' => $this->faker->randomElement($actions),
            'description' => $this->faker->sentence(),
        ];
    }
}
