<?php

namespace Modules\Mailer\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Mailer\Database\Seeders\Examples\ExampleTemplatesSeeder;
use Modules\Mailer\Database\Seeders\Migrations\MigrateTemplateTranslationsSeeder;
use Modules\Mailer\Database\Seeders\Setup\SetupLayoutsSeeder;
use Modules\Mailer\Database\Seeders\Setup\SetupVariablesSeeder;
use Modules\Mailer\Database\Seeders\Templates\MailerTemplateLayoutSeeder;

/**
 * MailerDatabaseSeeder
 *
 * Main coordinator seeder for the Mailer module.
 * Orchestrates the execution of all module seeders in proper dependency order.
 *
 * Execution Order:
 * 1. Setup seeders (system variables and base layouts)
 * 2. Template seeders (template-specific configurations)
 * 3. Example seeders (reference templates)
 * 4. Migration seeders (legacy data and migrations)
 */
class MailerDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('╔════════════════════════════════════════╗');
        $this->command->info('║   Seeding Mailer Module               ║');
        $this->command->info('╚════════════════════════════════════════╝');
        $this->command->info('');

        // ========================================
        // SETUP PHASE - Foundation
        // ========================================
        $this->command->info('SETUP PHASE - Foundational Data');
        $this->command->info('─────────────────────────────────────');

        $this->command->info('Step 1: Seeding system variables...');
        $this->call(SetupVariablesSeeder::class);
        $this->command->info('');

        $this->command->info('Step 2: Seeding base email layouts...');
        $this->call(SetupLayoutsSeeder::class);
        $this->command->info('');

        // ========================================
        // TEMPLATES PHASE
        // ========================================
        $this->command->info('TEMPLATES PHASE - Template Configuration');
        $this->command->info('─────────────────────────────────────');

        $this->command->info('Step 3: Seeding template-specific layouts...');
        $this->call(MailerTemplateLayoutSeeder::class);
        $this->command->info('');

        // ========================================
        // EXAMPLES PHASE
        // ========================================
        $this->command->info('EXAMPLES PHASE - Reference Templates');
        $this->command->info('─────────────────────────────────────');

        $this->command->info('Step 4: Seeding example templates...');
        $this->call(ExampleTemplatesSeeder::class);
        $this->command->info('');

        // ========================================
        // MIGRATIONS PHASE - Legacy Data
        // ========================================
        $this->command->info('MIGRATIONS PHASE - Legacy Data Migration');
        $this->command->info('─────────────────────────────────────');

        $this->command->info('Step 5: Migrating template translations...');
        $this->call(MigrateTemplateTranslationsSeeder::class);
        $this->command->info('');

        $this->command->info('╔════════════════════════════════════════╗');
        $this->command->info('║  ✓ Mailer Module Seeders Completed    ║');
        $this->command->info('╚════════════════════════════════════════╝');
    }
}
