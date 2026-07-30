<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HelpdeskHelpcenter\Database\Seeders\HelpCenterSeeder;

class HelpdeskSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionsSeeder::class,
            HelpdeskSettingsSeeder::class,
            HelpdeskPrioritiesSeeder::class,
            HelpdeskTicketCategoriesSeeder::class,
            BusinessHoursSeeder::class,
            SlaPoliciesSeeder::class,
            HelpdeskConversationStatusSeeder::class,
            HelpdeskGroupSeeder::class,
            HelpdeskTagSeeder::class,
            HelpdeskCannedReplySeeder::class,
            HelpdeskWhatsAppTemplateSeeder::class,
            ConversationViewSeeder::class,
            InboxChannelDefaultsSeeder::class,
        ]);

        if (class_exists(HelpCenterSeeder::class)) {
            $this->call(HelpCenterSeeder::class);
        }
    }
}
