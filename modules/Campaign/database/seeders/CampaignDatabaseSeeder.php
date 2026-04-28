<?php

namespace Modules\Campaign\Database\Seeders;

use Illuminate\Database\Seeder;

class CampaignDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Main seeder for the Campaign module that orchestrates
     * creating sample mailing lists, email templates, and automations
     * for testing and development purposes.
     */
    public function run(): void
    {
        $this->call(CampaignMaillistSeeder::class);
        $this->call(CampaignSeeder::class);
        $this->call(CampaignSegmentSeeder::class);
        $this->call(CampaignTemplateSeeder::class);
        $this->call(CampaignAutomationSeeder::class);

        $this->command->info('✅ Campaign module seeded successfully');
    }
}
