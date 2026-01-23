<?php

namespace Modules\Notification\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Modules\Activity\Models\ApplicationLog;

class CleanupOldLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:cleanup {--days=30 : Number of days to keep logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old logs from database and file system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoffDate = now()->subDays($days);

        $this->info('🧹 Starting log cleanup...');
        $this->line('Keeping logs from the last '.$days.' days');

        // Clean database logs
        $deletedDbLogs = ApplicationLog::where('created_at', '<', $cutoffDate)
            ->forceDelete();

        $this->info("✅ Deleted {$deletedDbLogs} database log entries");

        // Clean old file logs
        $deletedFiles = $this->cleanupFileSystemLogs($cutoffDate);
        $this->info("✅ Deleted {$deletedFiles} old log files");

        // Get storage stats
        $this->line('');
        $this->showStorageStats();

        $this->info('✨ Log cleanup completed successfully!');
    }

    /**
     * Clean up old log files from the file system
     */
    private function cleanupFileSystemLogs($cutoffDate): int
    {
        $logsPath = storage_path('logs');
        $deletedCount = 0;

        if (! is_dir($logsPath)) {
            return 0;
        }

        // Recursively delete old directories
        $this->deleteOldDirectories($logsPath, $cutoffDate, $deletedCount);

        return $deletedCount;
    }

    /**
     * Recursively delete old date directories
     */
    private function deleteOldDirectories($path, $cutoffDate, &$count): void
    {
        if (! is_dir($path)) {
            return;
        }

        $items = scandir($path);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $path.'/'.$item;

            // Check if this looks like a date directory (YYYY/MM/DD)
            if (is_dir($itemPath) && preg_match('/^\d{4}$/', $item)) {
                // This is a year directory, recurse into it
                $this->deleteOldDirectories($itemPath, $cutoffDate, $count);
            } elseif (is_file($itemPath) && strpos($item, 'laravel') !== false) {
                // Check file modification time
                $fileTime = filemtime($itemPath);
                if ($fileTime < $cutoffDate->timestamp) {
                    File::delete($itemPath);
                    $count++;
                    $this->line("  🗑️  Deleted: {$item}");
                }
            }
        }

        // Remove empty directories
        if (is_dir($path) && empty(array_diff(scandir($path), ['.', '..']))) {
            @rmdir($path);
        }
    }

    /**
     * Show storage statistics
     */
    private function showStorageStats(): void
    {
        $logsPath = storage_path('logs');

        if (! is_dir($logsPath)) {
            return;
        }

        $totalSize = $this->getDirectorySize($logsPath);
        $fileCount = $this->countFiles($logsPath);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Log Files', $fileCount],
                ['Total Size', $this->formatBytes($totalSize)],
                ['Database Logs', ApplicationLog::count()],
            ]
        );
    }

    /**
     * Get total size of directory
     */
    private function getDirectorySize($path): int
    {
        $size = 0;

        if (is_dir($path)) {
            foreach (scandir($path) as $file) {
                if ($file !== '.' && $file !== '..') {
                    $filePath = $path.'/'.$file;
                    $size += is_file($filePath) ? filesize($filePath) : $this->getDirectorySize($filePath);
                }
            }
        }

        return $size;
    }

    /**
     * Count files in directory
     */
    private function countFiles($path): int
    {
        $count = 0;

        if (is_dir($path)) {
            foreach (scandir($path) as $file) {
                if ($file !== '.' && $file !== '..') {
                    $filePath = $path.'/'.$file;
                    $count += is_file($filePath) ? 1 : $this->countFiles($filePath);
                }
            }
        }

        return $count;
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }
}
