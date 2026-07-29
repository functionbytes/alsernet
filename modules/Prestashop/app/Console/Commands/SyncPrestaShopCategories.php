<?php

namespace Modules\Prestashop\Console\Commands;

use Illuminate\Console\Command;
use Modules\Catalog\Models\Category;
use Modules\Prestashop\Services\CategorySyncService;

class SyncPrestaShopCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prestashop:sync-categories
                            {--direction=bidirectional : Sync direction (laravel_to_ps|ps_to_laravel|bidirectional)}
                            {--auto-resolve : Auto-resolve conflicts using default strategy}
                            {--dry-run : Show what would be synced without actually syncing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize categories between Laravel and PrestaShop';

    protected ?CategorySyncService $syncService;

    public function __construct(?CategorySyncService $syncService = null)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $direction = $this->option('direction');
        $autoResolve = $this->option('auto-resolve');
        $dryRun = $this->option('dry-run');

        $this->info('🔄 Starting PrestaShop category synchronization...');
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $stats = [
            'laravel_to_ps' => 0,
            'ps_to_laravel' => 0,
            'conflicts' => 0,
            'resolved' => 0,
            'failed' => 0,
        ];

        // Step 1: Sync Laravel → PrestaShop
        if (in_array($direction, ['laravel_to_ps', 'bidirectional'])) {
            $this->info('📤 Syncing Laravel categories to PrestaShop...');

            $categoriesToSync = Category::pendingSync()->get();

            if ($categoriesToSync->count() === 0) {
                $this->comment('  No categories need syncing to PrestaShop');
            } else {
                $bar = $this->output->createProgressBar($categoriesToSync->count());
                $bar->start();

                foreach ($categoriesToSync as $category) {
                    if (! $dryRun) {
                        $success = $this->syncService->syncToPrestaShop($category);
                        if ($success) {
                            $stats['laravel_to_ps']++;
                        } else {
                            $stats['failed']++;
                        }
                    } else {
                        $this->comment("  Would sync: {$category->title} (#{$category->id})");
                    }
                    $bar->advance();
                }

                $bar->finish();
                $this->newLine();
                $this->info("  ✓ Synced {$stats['laravel_to_ps']} categories to PrestaShop");
            }

            $this->newLine();
        }

        // Step 2: Detect and handle conflicts
        $this->info('🔍 Detecting conflicts...');

        $conflicts = $this->syncService->detectConflicts();
        $stats['conflicts'] = count($conflicts);

        if ($stats['conflicts'] === 0) {
            $this->info('  ✓ No conflicts detected');
        } else {
            $this->warn("  ⚠️  Found {$stats['conflicts']} conflicts");

            if ($autoResolve) {
                $this->info('  🔧 Auto-resolving conflicts...');

                $strategy = config('categories.default_conflict_resolution', 'manual');
                $bar = $this->output->createProgressBar(count($conflicts));
                $bar->start();

                foreach ($conflicts as $mapping) {
                    if (! $dryRun) {
                        $success = $this->syncService->resolveConflict($mapping, $strategy);
                        if ($success) {
                            $stats['resolved']++;
                        } else {
                            $stats['failed']++;
                        }
                    }
                    $bar->advance();
                }

                $bar->finish();
                $this->newLine();
                $this->info("  ✓ Resolved {$stats['resolved']} conflicts using strategy: {$strategy}");
            } else {
                $this->comment('  Run with --auto-resolve to automatically resolve conflicts');
            }
        }

        $this->newLine();

        // Step 3: Display summary
        $this->info('📊 Synchronization Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Laravel → PrestaShop', $stats['laravel_to_ps']],
                ['PrestaShop → Laravel', $stats['ps_to_laravel']],
                ['Conflicts Detected', $stats['conflicts']],
                ['Conflicts Resolved', $stats['resolved']],
                ['Failed Operations', $stats['failed']],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn('ℹ️  This was a dry run. Run without --dry-run to actually sync.');
        }

        if ($stats['failed'] > 0) {
            $this->newLine();
            $this->error("⚠️  {$stats['failed']} operations failed. Check logs for details.");

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('✅ Synchronization completed successfully!');

        return self::SUCCESS;
    }
}
