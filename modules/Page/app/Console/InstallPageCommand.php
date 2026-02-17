<?php

namespace Modules\Page\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallPageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'page:install {--seed : Run seeders after installation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Page module';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Installing Page Module...');
        $this->newLine();

        // Step 1: Run migrations
        $this->info('Step 1: Running migrations...');
        try {
            Artisan::call('migrate', ['--force' => true]);
            $this->info('✓ Migrations completed successfully');
        } catch (\Exception $e) {
            $this->error('✗ Migration failed: '.$e->getMessage());

            return Command::FAILURE;
        }

        $this->newLine();

        // Step 2: Publish assets (optional)
        if ($this->confirm('Do you want to publish the configuration file?', false)) {
            $this->info('Step 2: Publishing configuration...');
            Artisan::call('vendor:publish', [
                '--tag' => 'page-config',
                '--force' => true,
            ]);
            $this->info('✓ Configuration published');
        }

        $this->newLine();

        // Step 3: Run seeders if requested
        if ($this->option('seed') || $this->confirm('Do you want to run seeders to create example pages?', true)) {
            $this->info('Step 3: Running seeders...');
            try {
                Artisan::call('db:seed', [
                    '--class' => 'Modules\\Page\\Database\\Seeders\\PageDatabaseSeeder',
                ]);
                $this->info('✓ Seeders completed successfully');
            } catch (\Exception $e) {
                $this->error('✗ Seeder failed: '.$e->getMessage());
            }
        }

        $this->newLine();

        // Step 4: Clear cache
        $this->info('Step 4: Clearing cache...');
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        $this->info('✓ Cache cleared');

        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('✓ Page Module installed successfully!');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        // Show next steps
        $this->showNextSteps();

        return Command::SUCCESS;
    }

    /**
     * Show next steps after installation.
     */
    protected function showNextSteps(): void
    {
        $this->info('Next Steps:');
        $this->newLine();
        $this->line('1. Visit settings pages: /settings/pages');
        $this->line('2. Create your first page');
        $this->line('3. Customize templates in resources/views/public/templates/');
        $this->line('4. Configure permissions if needed');
        $this->line('5. Adjust the layout in resources/views/components/layouts/master.blade.php');
        $this->newLine();
        $this->info('For more information, check the documentation:');
        $this->line('- README.md');
        $this->line('- INSTALLATION.md');
        $this->newLine();
    }
}
