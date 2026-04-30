<?php

namespace Modules\Chat\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Conversations\ConversationPriority;

class ConversationPrioritySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $priorities = [
            ['name' => 'Low', 'slug' => 'low', 'color' => '#90ee90', 'order' => 1],
            ['name' => 'Medium', 'slug' => 'medium', 'color' => '#ffc107', 'order' => 2],
            ['name' => 'High', 'slug' => 'high', 'color' => '#ff9800', 'order' => 3],
            ['name' => 'Urgent', 'slug' => 'urgent', 'color' => '#ff6b6b', 'order' => 4],
        ];

        $account = Account::first();
        if ($account) {
            foreach ($priorities as $priority) {
                ConversationPriority::firstOrCreate(
                    ['account_id' => $account->id, 'slug' => $priority['slug']],
                    array_merge($priority, ['account_id' => $account->id, 'is_active' => true])
                );
            }
        }
    }
}
