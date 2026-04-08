<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Helpdesk\Models\ConversationView;

class ConversationViewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $systemViews = [
            [
                'name' => 'Inbox',
                'description' => 'All active conversations',
                'filters' => ['is_open' => true, 'is_archived' => false],
                'is_public' => true,
                'is_system' => true,
                'is_default' => true,
                'order' => 1,
            ],
            [
                'name' => 'Mine',
                'description' => 'Conversations assigned to me',
                'filters' => ['assignee' => 'mine', 'is_open' => true, 'is_archived' => false],
                'is_public' => false,
                'is_system' => true,
                'is_default' => false,
                'order' => 2,
            ],
            [
                'name' => 'Unassigned',
                'description' => 'Conversations without an assignee',
                'filters' => ['assignee' => 'unassigned', 'is_open' => true, 'is_archived' => false],
                'is_public' => true,
                'is_system' => true,
                'is_default' => false,
                'order' => 3,
            ],
            [
                'name' => 'Closed',
                'description' => 'Closed conversations',
                'filters' => ['is_open' => false, 'is_archived' => false],
                'is_public' => true,
                'is_system' => true,
                'is_default' => false,
                'order' => 4,
            ],
            [
                'name' => 'All',
                'description' => 'All conversations (open and closed)',
                'filters' => ['is_archived' => false],
                'is_public' => true,
                'is_system' => true,
                'is_default' => false,
                'order' => 5,
            ],
        ];

        foreach ($systemViews as $view) {
            ConversationView::updateOrCreate(
                ['name' => $view['name'], 'is_system' => true],
                $view
            );
        }
    }
}
