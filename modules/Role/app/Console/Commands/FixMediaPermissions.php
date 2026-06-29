<?php

namespace Modules\Role\Console\Commands;

use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FixMediaPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-media-permissions {--dry-run : Show what would be changed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix permissions for all media files to be accessible via web server';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $mediaPath = public_path('media');

        if (! is_dir($mediaPath)) {
            $this->error("Media directory not found: {$mediaPath}");

            return 1;
        }

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in DRY-RUN mode. No changes will be made.');
        }

        $this->info("Processing media files in: {$mediaPath}");

        $dirCount = 0;
        $fileCount = 0;

        try {
            // Recursively iterate through all files and directories
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($mediaPath)
            );

            foreach ($iterator as $path) {
                if (is_dir($path)) {
                    // Set directory permissions to 755
                    if (! $dryRun) {
                        @chmod($path, 0755);
                    }
                    $dirCount++;
                    $this->line("  DIR:  {$path} → 755", 'comment');
                } else {
                    // Set file permissions to 644
                    if (! $dryRun) {
                        @chmod($path, 0644);
                    }
                    $fileCount++;
                    $this->line("  FILE: {$path} → 644", 'comment');
                }
            }

            // Also fix the main media directory itself
            if (! $dryRun) {
                @chmod($mediaPath, 0755);
            }
            $this->line("  DIR:  {$mediaPath} → 755", 'comment');

            $this->newLine();
            $this->info('✓ Complete!');
            $this->line("  Directories processed: {$dirCount}");
            $this->line("  Files processed: {$fileCount}");

            if ($dryRun) {
                $this->info('This was a DRY-RUN. Run without --dry-run to apply changes.');
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Error processing media files: {$e->getMessage()}");

            return 1;
        }
    }
}
