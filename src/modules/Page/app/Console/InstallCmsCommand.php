<?php

namespace Modules\Page\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallCmsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:install
                            {--fresh : Drop all tables and migrate from scratch}
                            {--seed : Run seeders after migration}
                            {--force : Force the operation to run in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all CMS modules (Page, Menu, Seo, Shortcode, Sitemap)';

    /**
     * CMS modules to install
     *
     * @var array
     */
    protected $modules = [
        'Page',
        'Menu',
        'Seo',
        'Shortcode',
        'Sitemap',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║           CMS MODULES INSTALLATION                        ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Warning for production
        if ($this->laravel->environment('production') && ! $this->option('force')) {
            $this->error('Application is in production mode!');
            $this->warn('Use --force flag to run in production.');

            return self::FAILURE;
        }

        // Confirm action if fresh install
        if ($this->option('fresh')) {
            $this->warn('⚠️  WARNING: This will DROP all CMS tables and data!');

            if (! $this->confirm('Are you sure you want to continue?', false)) {
                $this->info('Installation cancelled.');

                return self::FAILURE;
            }
        }

        // Step 1: Verify modules exist
        $this->info('📦 Step 1: Verifying modules...');
        if (! $this->verifyModules()) {
            return self::FAILURE;
        }

        // Step 2: Run migrations
        $this->info('🗄️  Step 2: Running migrations...');
        $this->runMigrations();

        // Step 3: Run seeders (if requested)
        if ($this->option('seed')) {
            $this->info('🌱 Step 3: Running seeders...');
            $this->runSeeders();
        }

        // Step 4: Generate sitemap
        $this->info('🗺️  Step 4: Generating sitemap...');
        $this->generateSitemap();

        // Step 5: Clear caches
        $this->info('🧹 Step 5: Clearing caches...');
        $this->clearCaches();

        // Summary
        $this->newLine();
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║         ✓ CMS INSTALLATION COMPLETED!                     ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->displaySummary();

        return self::SUCCESS;
    }

    /**
     * Verify that all required modules exist
     */
    protected function verifyModules(): bool
    {
        $missing = [];

        foreach ($this->modules as $module) {
            $path = base_path("modules/{$module}");

            if (! is_dir($path)) {
                $missing[] = $module;
                $this->error("  ✗ Module not found: {$module}");
            } else {
                $this->line("  ✓ {$module} module found");
            }
        }

        if (! empty($missing)) {
            $this->newLine();
            $this->error('Missing modules: '.implode(', ', $missing));
            $this->warn('Please ensure all CMS modules are installed.');

            return false;
        }

        $this->newLine();

        return true;
    }

    /**
     * Run migrations for all CMS modules
     */
    protected function runMigrations(): void
    {
        foreach ($this->modules as $module) {
            $this->line("  → Migrating {$module}...");

            try {
                $command = $this->option('fresh')
                    ? 'module:migrate-fresh'
                    : 'module:migrate';

                Artisan::call($command, [
                    'module' => $module,
                    '--force' => true,
                ]);

                $this->info("  ✓ {$module} migrated successfully");
            } catch (\Exception $e) {
                $this->error("  ✗ Error migrating {$module}: ".$e->getMessage());
            }
        }

        $this->newLine();
    }

    /**
     * Run seeders for all CMS modules
     */
    protected function runSeeders(): void
    {
        // Page module seeder (includes CmsPermissionsSeeder)
        $this->line('  → Seeding Page module...');

        try {
            Artisan::call('db:seed', [
                '--class' => 'Modules\\Page\\Database\\Seeders\\PageDatabaseSeeder',
                '--force' => true,
            ]);
            $this->info('  ✓ Page module seeded successfully');
        } catch (\Exception $e) {
            $this->error('  ✗ Error seeding Page: '.$e->getMessage());
        }

        // Menu module seeder (if exists)
        if (class_exists('Modules\\Menu\\Database\\Seeders\\MenuDatabaseSeeder')) {
            $this->line('  → Seeding Menu module...');

            try {
                Artisan::call('db:seed', [
                    '--class' => 'Modules\\Menu\\Database\\Seeders\\MenuDatabaseSeeder',
                    '--force' => true,
                ]);
                $this->info('  ✓ Menu module seeded successfully');
            } catch (\Exception $e) {
                $this->error('  ✗ Error seeding Menu: '.$e->getMessage());
            }
        }

        $this->newLine();
    }

    /**
     * Generate sitemap
     */
    protected function generateSitemap(): void
    {
        try {
            if (class_exists('Modules\\Seo\\Console\\Commands\\GenerateSitemapCommand')) {
                Artisan::call('sitemap:generate');
                $this->info('  ✓ Sitemap generated successfully');
            } else {
                $this->warn('  ⚠ Sitemap command not available');
            }
        } catch (\Exception $e) {
            $this->warn('  ⚠ Could not generate sitemap: '.$e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Clear all caches
     */
    protected function clearCaches(): void
    {
        $caches = [
            'cache:clear' => 'Application cache',
            'config:clear' => 'Configuration cache',
            'route:clear' => 'Route cache',
            'view:clear' => 'View cache',
        ];

        foreach ($caches as $command => $description) {
            try {
                Artisan::call($command);
                $this->info("  ✓ {$description} cleared");
            } catch (\Exception $e) {
                $this->warn("  ⚠ Could not clear {$description}");
            }
        }

        $this->newLine();
    }

    /**
     * Display installation summary
     */
    protected function displaySummary(): void
    {
        $this->info('📋 Installation Summary:');
        $this->newLine();

        $this->table(
            ['Module', 'Status', 'Features'],
            [
                [
                    'Page',
                    '✓ Installed',
                    'CRUD, Templates, SEO, Media, Sitemap',
                ],
                [
                    'Menu',
                    '✓ Installed',
                    'Hierarchical menus, Drag & Drop, Locations',
                ],
                [
                    'Seo',
                    '✓ Installed',
                    'Meta tags, Open Graph, Twitter Cards',
                ],
                [
                    'Shortcode',
                    '✓ Installed',
                    '12 predefined shortcodes, Custom shortcodes',
                ],
                [
                    'Sitemap',
                    '✓ Installed',
                    'XML generation, Auto-discovery, Ping',
                ],
            ]
        );

        $this->newLine();
        $this->info('📖 Next Steps:');
        $this->line('  1. Create your first page:');
        $this->line('     php artisan tinker');
        $this->line('     > Modules\Page\Models\Page::factory()->create()');
        $this->newLine();
        $this->line('  2. Access settings panel at: /settings/pages');
        $this->newLine();
        $this->line('  3. View public pages at: /pages');
        $this->newLine();
        $this->line('  4. Check sitemap at: /sitemap.xml');
        $this->newLine();

        $this->info('🔐 Default CMS Roles:');
        $this->line('  • cms-settings    - Full CMS access');
        $this->line('  • cms-editor   - Can edit content');
        $this->line('  • cms-author   - Can create own content');
        $this->line('  • cms-viewer   - Read-only access');
        $this->newLine();

        $this->warn('⚠️  Remember to:');
        $this->line('  • Configure your menu at: /settings/menus');
        $this->line('  • Assign CMS roles to users');
        $this->line('  • Schedule sitemap generation (daily)');
        $this->line('  • Review SEO settings');
    }
}
