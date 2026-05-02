<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;

class HelpdeskSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionsSeeder::class,
            HelpdeskConversationStatusSeeder::class,
            HelpdeskGroupSeeder::class,
            HelpdeskTagSeeder::class,
            HelpdeskCannedReplySeeder::class,
            HelpdeskWhatsAppTemplateSeeder::class,
            ConversationViewSeeder::class,
            HelpCenterSeeder::class,
        ]);
    }
}
