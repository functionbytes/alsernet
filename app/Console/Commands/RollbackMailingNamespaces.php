<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RollbackMailingNamespaces extends Command
{
    protected $signature = 'mailing:rollback-namespaces
        {--files= : Comma-separated list of files to rollback}
        {--all : Rollback all .bak-namespace files}
        {--keep-backups : Keep backup files after rollback}';

    protected $description = 'Rollback namespace migration by restoring from .bak-namespace files';

    protected array $stats = [
        'files_found' => 0,
        'files_restored' => 0,
        'files_failed' => 0,
        'errors' => [],
    ];

    public function handle(): int
    {
        $this->info('🔄 Starting namespace migration rollback...');
        $this->newLine();

        $backupFiles = $this->findBackupFiles();

        if (empty($backupFiles)) {
            $this->warn('⚠️  No backup files found.');
            $this->info('Backup files should have .bak-namespace extension.');

            return Command::SUCCESS;
        }

        $this->stats['files_found'] = count($backupFiles);
        $this->info('📁 Found {count} backup files', ['count' => count($backupFiles)]);
        $this->newLine();

        // Confirm before proceeding
        if (! $this->confirm('Do you want to restore these files from backup?')) {
            $this->warn('Rollback cancelled.');

            return Command::SUCCESS;
        }

        $this->restoreFiles($backupFiles);
        $this->displayResults();

        return Command::SUCCESS;
    }

    protected function findBackupFiles(): array
    {
        $backupFiles = [];

        // Check if specific files were provided
        if ($this->option('files')) {
            $fileList = explode(',', $this->option('files'));
            foreach ($fileList as $file) {
                $backupPath = base_path(trim($file)).'.bak-namespace';
                if (File::exists($backupPath)) {
                    $backupFiles[] = $backupPath;
                } else {
                    $this->warn("⚠️  Backup not found: {$backupPath}");
                }
            }

            return $backupFiles;
        }

        // Find all backup files in Mailing module
        $baseDir = base_path('modules/Mailing');

        $backupFiles = array_merge(
            File::glob("{$baseDir}/app/**/*.bak-namespace"),
            File::glob("{$baseDir}/resources/**/*.bak-namespace")
        );

        return $backupFiles;
    }

    protected function restoreFiles(array $backupFiles): void
    {
        $progressBar = $this->output->createProgressBar(count($backupFiles));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $progressBar->setMessage('Starting...');

        foreach ($backupFiles as $backupPath) {
            $originalPath = str_replace('.bak-namespace', '', $backupPath);
            $progressBar->setMessage(basename($originalPath));

            $this->restoreFile($backupPath, $originalPath);

            $progressBar->advance();
        }

        $progressBar->setMessage('Complete');
        $progressBar->finish();
        $this->newLine(2);
    }

    protected function restoreFile(string $backupPath, string $originalPath): void
    {
        try {
            // Restore from backup
            File::copy($backupPath, $originalPath);

            $this->stats['files_restored']++;

            // Remove backup file unless --keep-backups is set
            if (! $this->option('keep-backups')) {
                File::delete($backupPath);
            }

            // Log rollback
            $this->logRollback($originalPath);
        } catch (\Exception $e) {
            $this->stats['files_failed']++;
            $this->stats['errors'][] = [
                'file' => $originalPath,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function logRollback(string $filePath): void
    {
        $logPath = storage_path('logs/namespace-migration-rollback.log');
        $timestamp = now()->toDateTimeString();
        $relativePath = str_replace(base_path().'/', '', $filePath);

        $logEntry = "[{$timestamp}] Rolled back: {$relativePath}\n";

        File::append($logPath, $logEntry);
    }

    protected function displayResults(): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('                    Rollback Results                       ');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Backup Files Found', $this->stats['files_found']],
                ['Files Restored', $this->stats['files_restored']],
                ['Files Failed', $this->stats['files_failed']],
            ]
        );

        $this->newLine();

        if ($this->stats['files_restored'] > 0) {
            $this->info('✅ Rollback completed successfully!');

            if ($this->option('keep-backups')) {
                $this->info('💾 Backup files retained');
            } else {
                $this->info('🗑️  Backup files deleted');
            }

            $logPath = storage_path('logs/namespace-migration-rollback.log');
            $this->info("📋 Rollback logged to: {$logPath}");
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
    }
}
