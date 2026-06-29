<?php

namespace Modules\Mailer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MigrateMailingNamespaces extends Command
{
    protected $signature = 'mailing:migrate-namespaces
        {--dry-run : Show changes without modifying files}
        {--no-backup : Skip creating backups}
        {--force : Skip confirmation prompt}
        {--files= : Comma-separated list of files to migrate}
        {--controllers : Only migrate controllers}
        {--views : Only migrate views}
        {--models : Only migrate models}
        {--detailed : Show detailed processing information}';

    protected $description = 'Migrate Acelle\ namespaces to Modules\Mailing\ with validation and backup';

    protected array $namespaceMap = [];

    protected array $stats = [
        'files_processed' => 0,
        'files_modified' => 0,
        'files_unchanged' => 0,
        'files_failed' => 0,
        'replacements_made' => 0,
        'errors' => [],
    ];

    protected float $startTime;

    public function handle(): int
    {
        $this->startTime = microtime(true);

        $this->info('🚀 Starting Acelle to Modules\Mailing namespace migration...');
        $this->newLine();

        // Load namespace mapping
        if (! $this->loadNamespaceMap()) {
            return Command::FAILURE;
        }

        // Validate target classes exist (skip if dry-run or force)
        if (! $this->option('dry-run') && ! $this->option('force')) {
            $this->info('🔍 Validating target classes...');
            if (! $this->validateTargetClasses()) {
                $this->error('❌ Validation failed. Run with --dry-run to see what would change, or --force to skip validation.');

                return Command::FAILURE;
            }
            $this->info('✅ All target classes validated successfully.');
            $this->newLine();
        }

        // Get files to process
        $files = $this->getFilesToProcess();

        if (empty($files)) {
            $this->warn('⚠️  No files found to process.');

            return Command::SUCCESS;
        }

        $this->info('📁 Found '.count($files).' files to process');
        $this->newLine();

        // Confirm before proceeding (unless dry-run or force)
        if (! $this->option('dry-run') && ! $this->option('force') && ! $this->confirm('Do you want to proceed with the migration?')) {
            $this->warn('Migration cancelled.');

            return Command::SUCCESS;
        }

        // Process files
        $this->processFiles($files);

        // Display results
        $this->displayResults();

        return Command::SUCCESS;
    }

    protected function loadNamespaceMap(): bool
    {
        $configPath = config_path('namespace-migration-map.json');

        if (! File::exists($configPath)) {
            $this->error("❌ Config file not found: {$configPath}");

            return false;
        }

        $config = json_decode(File::get($configPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('❌ Invalid JSON in config file: '.json_last_error_msg());

            return false;
        }

        // Merge all namespace mappings
        $this->namespaceMap = array_merge(
            $config['models'] ?? [],
            $config['libraries'] ?? [],
            $config['jobs'] ?? [],
            $config['events'] ?? []
        );

        $this->info('✅ Loaded '.count($this->namespaceMap).' namespace mappings');

        return true;
    }

    protected function validateTargetClasses(): bool
    {
        $missingClasses = [];

        foreach ($this->namespaceMap as $old => $new) {
            // Skip global App\ models (they should already exist)
            if (str_starts_with($new, 'App\\Models\\')) {
                continue;
            }

            // Skip special cases marked for manual review
            if (is_null($new)) {
                continue;
            }

            // Convert namespace to file path
            $filePath = $this->namespaceToPath($new);

            if (! File::exists($filePath)) {
                $missingClasses[] = [
                    'old' => $old,
                    'new' => $new,
                    'expected_path' => $filePath,
                ];
            }
        }

        if (! empty($missingClasses)) {
            $this->error('❌ Missing target classes:');
            $this->table(
                ['Old Namespace', 'New Namespace', 'Expected Path'],
                array_map(fn ($item) => array_values($item), $missingClasses)
            );

            return false;
        }

        return true;
    }

    protected function namespaceToPath(string $namespace): string
    {
        $relativePath = str_replace('\\', '/', $namespace).'.php';
        $relativePath = str_replace('Modules/Mailing/', 'modules/Mailing/app/', $relativePath);
        $relativePath = str_replace('App/', 'app/', $relativePath);

        return base_path($relativePath);
    }

    protected function getFilesToProcess(): array
    {
        $files = [];

        // Check if specific files were provided
        if ($this->option('files')) {
            $fileList = explode(',', $this->option('files'));
            foreach ($fileList as $file) {
                $fullPath = base_path(trim($file));
                if (File::exists($fullPath)) {
                    $files[] = $fullPath;
                } else {
                    $this->warn("⚠️  File not found: {$file}");
                }
            }

            return $files;
        }

        $baseDir = base_path('modules/Mailing');

        // Controllers
        if ($this->option('controllers') || (! $this->option('views') && ! $this->option('models'))) {
            $files = array_merge(
                $files,
                $this->findPhpFiles("{$baseDir}/app/Http/Controllers", '*.php')
            );
        }

        // Views
        if ($this->option('views') || (! $this->option('controllers') && ! $this->option('models'))) {
            $files = array_merge(
                $files,
                $this->findPhpFiles("{$baseDir}/resources/views", '*.blade.php')
            );
        }

        // Models (for verification purposes)
        if ($this->option('models')) {
            $files = array_merge(
                $files,
                $this->findPhpFiles("{$baseDir}/app/Models", '*.php')
            );
        }

        // Remove .bak files unless explicitly included
        $files = array_filter($files, fn ($file) => ! str_ends_with($file, '.bak'));

        return array_unique($files);
    }

    protected function findPhpFiles(string $directory, string $pattern): array
    {
        if (! File::isDirectory($directory)) {
            return [];
        }

        return File::glob("{$directory}/**/{$pattern}");
    }

    protected function processFiles(array $files): void
    {
        $progressBar = $this->output->createProgressBar(count($files));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $progressBar->setMessage('Starting...');

        foreach ($files as $file) {
            $progressBar->setMessage(basename($file));

            $this->processFile($file);

            $progressBar->advance();
        }

        $progressBar->setMessage('Complete');
        $progressBar->finish();
        $this->newLine(2);
    }

    protected function processFile(string $filePath): void
    {
        try {
            $this->stats['files_processed']++;

            $originalContent = File::get($filePath);
            $modifiedContent = $originalContent;
            $replacements = 0;

            // Process each namespace mapping
            foreach ($this->namespaceMap as $oldNamespace => $newNamespace) {
                if (is_null($newNamespace)) {
                    continue; // Skip special cases requiring manual review
                }

                // Escape backslashes for regex
                $oldEscaped = str_replace('\\', '\\\\', $oldNamespace);
                $newEscaped = str_replace('\\', '\\\\', $newNamespace);

                // Pattern 1: use statements - use Acelle\Model\Campaign;
                $pattern1 = "/use\s+{$oldEscaped};/";
                $replacement1 = "use {$newEscaped};";
                $modifiedContent = preg_replace($pattern1, $replacement1, $modifiedContent, -1, $count1);
                $replacements += $count1;

                // Pattern 2: Inline fully qualified - \Acelle\Model\Campaign or Acelle\Model\Campaign
                $pattern2 = "/\\\\?{$oldEscaped}(?=\\s|::|;|,|\(|\)|'|\"|\\[|\\]|<|>|\\.)/";

                $replacement2 = "\\{$newEscaped}";
                $modifiedContent = preg_replace($pattern2, $replacement2, $modifiedContent, -1, $count2);
                $replacements += $count2;
            }

            if ($modifiedContent !== $originalContent) {
                $this->stats['files_modified']++;
                $this->stats['replacements_made'] += $replacements;

                if ($this->option('detailed')) {
                    $this->info("  ✏️  Modified: {$filePath} ({$replacements} replacements)");
                }

                if (! $this->option('dry-run')) {
                    // Create backup
                    if (! $this->option('no-backup')) {
                        $this->createBackup($filePath);
                    }

                    // Write modified content
                    File::put($filePath, $modifiedContent);

                    // Log changes
                    $this->logChange($filePath, $replacements);
                }
            } else {
                $this->stats['files_unchanged']++;

                if ($this->option('detailed')) {
                    $this->comment("  ⏭️  Unchanged: {$filePath}");
                }
            }
        } catch (\Exception $e) {
            $this->stats['files_failed']++;
            $this->stats['errors'][] = [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ];

            if ($this->option('detailed')) {
                $this->error("  ❌ Failed: {$filePath} - {$e->getMessage()}");
            }
        }
    }

    protected function createBackup(string $filePath): void
    {
        $backupPath = $filePath.'.bak-namespace';
        File::copy($filePath, $backupPath);
    }

    protected function logChange(string $filePath, int $replacements): void
    {
        $logPath = storage_path('logs/namespace-migration.log');
        $timestamp = now()->toDateTimeString();
        $relativePath = str_replace(base_path().'/', '', $filePath);

        $logEntry = "[{$timestamp}] {$relativePath}: {$replacements} replacements\n";

        File::append($logPath, $logEntry);
    }

    protected function displayResults(): void
    {
        $duration = round(microtime(true) - $this->startTime, 2);

        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('                    Migration Results                      ');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Files Processed', $this->stats['files_processed']],
                ['Files Modified', $this->stats['files_modified']],
                ['Files Unchanged', $this->stats['files_unchanged']],
                ['Files Failed', $this->stats['files_failed']],
                ['Total Replacements', $this->stats['replacements_made']],
                ['Execution Time', "{$duration}s"],
            ]
        );

        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('🔍 DRY RUN MODE - No files were actually modified.');
            $this->info('Run without --dry-run to apply changes.');
        } else {
            $this->info('✅ Migration completed successfully!');

            if (! $this->option('no-backup')) {
                $this->info('💾 Backups created with .bak-namespace extension');
            }

            $logPath = storage_path('logs/namespace-migration.log');
            $this->info("📋 Changes logged to: {$logPath}");
        }

        $this->newLine();

        // Display errors if any
        if (! empty($this->stats['errors'])) {
            $this->error('❌ Errors encountered:');
            $this->table(
                ['File', 'Error'],
                array_map(fn ($error) => [$error['file'], $error['error']], $this->stats['errors'])
            );
        }

        // Next steps
        if (! $this->option('dry-run') && $this->stats['files_modified'] > 0) {
            $this->newLine();
            $this->info('📝 Recommended next steps:');
            $this->line('  1. Run tests: php artisan test');
            $this->line('  2. Review changes: git diff');
            $this->line('  3. Check for any remaining Acelle\ references:');
            $this->line('     grep -r "Acelle\\\\" modules/Mailing/ --include="*.php" --include="*.blade.php"');
            $this->line('  4. If issues arise, rollback with: php artisan mailing:rollback-namespaces');
        }
    }
}
