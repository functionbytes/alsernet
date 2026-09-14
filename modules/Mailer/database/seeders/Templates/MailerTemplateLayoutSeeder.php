<?php

namespace Modules\Mailer\Database\Seeders\Templates;

use Illuminate\Database\Seeder;

/**
 * MailerTemplateLayoutSeeder
 *
 * DEPRECATED: This seeder is deprecated. Template layouts are now seeded
 * by SetupLayoutsSeeder in the Setup phase.
 *
 * Kept for backwards compatibility.
 */
class MailerTemplateLayoutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('✓ Template layouts already seeded by SetupLayoutsSeeder');
        $this->command->line('  (This seeder is deprecated and will be removed in a future version)');
    }
}
