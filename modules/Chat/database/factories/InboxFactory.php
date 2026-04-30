<?php

namespace Modules\Chat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Inbox\Inbox;

class InboxFactory extends Factory
{
    protected $model = Inbox::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'name' => fake()->words(3, true),
            'channel_type' => 'Modules\\Chat\\Models\\Channels\\Web',
            'channel_id' => 1,
            'greeting_enabled' => false,
            'greeting_message' => null,
        ];
    }
}
