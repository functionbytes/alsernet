<?php

namespace Modules\Backup\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class CreateBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $backupTypes;

    protected $backupConfig;

    protected $dbSettings;

    /**
     * Create a new job instance.
     */
    public function __construct(array $backupTypes, array $backupConfig, ?array $dbSettings = null)
    {
        $this->backupTypes = $backupTypes;
        $this->backupConfig = $backupConfig;
        $this->dbSettings = $dbSettings;
    }

    /**
     * Execute the job - Create selective backups using PHP's ZipArchive
     * This respects user selections: only backs up what they chose
     */
    public function handle(): void
    {
        try {
            Log::info('Starting backup job with types: '.implode(', ', $this->backupTypes));
            Log::info('Files to backup: '.json_encode($this->backupConfig['files']['include'] ?? []));

            // Create backup directory (same as Spatie)
            $backupDir = storage_path('app/'.config('app.name', 'backup'));
            if (! is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Generate backup filename with timestamp
            $timestamp = date('Y-m-d-H-i-s');
            $backupFile = $backupDir.'/'.$timestamp.'.zip';

            // Create ZIP archive
            $zip = new ZipArchive;
            if ($zip->open($backupFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('Cannot create backup file: '.$backupFile);
            }

            // Add ONLY the selected files/directories (if any)
            $filesToBackup = $this->backupConfig['files']['include'] ?? [];
            if (! empty($filesToBackup)) {
                foreach ($filesToBackup as $path) {
                    if (file_exists($path)) {
                        $this->addPathToZip($zip, $path, '');
                        Log::info('Added to backup: '.$path);
                    }
                }
            } else {
                Log::info('No files selected for backup');
            }

            // Add database dump if selected
            if (in_array('database', $this->backupTypes) && $this->dbSettings) {
                $this->addDatabaseDumpToZip($zip, $timestamp);
            }

            // Close ZIP archive
            $zip->close();

            $fileSize = filesize($backupFile);
            Log::info('Backup file created: '.basename($backupFile).' ('.$this->formatBytes($fileSize).')');

        } catch (\Exception $e) {
            Log::error('Backup job failed: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Recursively add a path (file or directory) to the ZIP archive
     */
    private function addPathToZip(ZipArchive $zip, string $path, string $arcPath): void
    {
        if (! file_exists($path)) {
            return;
        }

        $baseName = basename($path);

        if (is_dir($path)) {
            // For directories, recursively add contents
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($path) + 1);
                $zipPath = $arcPath ? $arcPath.'/'.$baseName.'/'.$relativePath : $baseName.'/'.$relativePath;

                if (is_dir($file)) {
                    $zip->addEmptyDir($zipPath);
                } else {
                    $zip->addFile($filePath, $zipPath);
                }
            }
        } else {
            // For single files, add directly
            $zipPath = $arcPath ? $arcPath.'/'.$baseName : $baseName;
            $zip->addFile($path, $zipPath);
        }
    }

    /**
     * Create and add database dump to ZIP (if selected)
     */
    private function addDatabaseDumpToZip(ZipArchive $zip, string $timestamp): void
    {
        try {
            if (! $this->dbSettings) {
                Log::warning('No database backups provided');

                return;
            }

            $dbHost = $this->dbSettings['db_host'] ?? 'localhost';
            $dbUser = $this->dbSettings['db_username'];
            $dbPass = $this->dbSettings['db_password'];
            $dbName = $this->dbSettings['db_database'];
            $dbPort = $this->dbSettings['db_port'] ?? 3306;

            Log::info('Creating database dump for: '.$dbName.'@'.$dbHost);

            // Build mysqldump command - try multiple paths
            $mysqldumpPath = $this->getMysqldumpPath();

            $command = [
                $mysqldumpPath,
                '-h', $dbHost,
                '-P', (string) $dbPort,
                '-u', $dbUser,
                '-p'.($dbPass ?? ''),  // password without space
                $dbName,
            ];

            // Prepare environment variables (as backup)
            $env = array_merge($_ENV, ['MYSQL_PWD' => $dbPass ?? '']);

            // Execute mysqldump
            $cmdStr = implode(' ', array_map('escapeshellarg', $command));
            Log::info('Executing mysqldump command: '.substr($cmdStr, 0, 100).'...');

            $pipes = [];
            $process = proc_open(
                $cmdStr,
                [
                    0 => ['pipe', 'r'],  // stdin
                    1 => ['pipe', 'w'],  // stdout
                    2 => ['pipe', 'w'],  // stderr
                ],
                $pipes,
                null,
                $env
            );

            if (! is_resource($process)) {
                Log::error('Failed to execute mysqldump - proc_open returned: '.var_export($process, true));

                return;
            }

            // Close stdin since we don't need it
            fclose($pipes[0]);

            // Read output
            $output = '';
            while (! feof($pipes[1])) {
                $output .= fread($pipes[1], 8192);
            }
            fclose($pipes[1]);

            // Read errors
            $errors = '';
            while (! feof($pipes[2])) {
                $errors .= fread($pipes[2], 8192);
            }
            fclose($pipes[2]);

            $exitCode = proc_close($process);
            Log::info('mysqldump exit code: '.$exitCode.', output size: '.strlen($output).' bytes');

            if ($errors && ! empty(trim($errors))) {
                Log::warning('mysqldump stderr: '.substr($errors, 0, 200));
            }

            if (! $output) {
                Log::error('mysqldump returned empty output');

                return;
            }

            // Check if output contains valid SQL dump header
            // Valid dumps start with "-- MySQL dump" or "-- MariaDB dump"
            if (! preg_match('/--\s*(MySQL|MariaDB)\s+dump/i', $output)) {
                // Check for actual error messages (mysqldump command not found, connection errors, etc.)
                if (preg_match('/command not found|Connection refused|Access denied|Unknown database/i', $output)) {
                    Log::error('mysqldump error detected: '.substr($output, 0, 200));

                    return;
                }
                // If not an error, still log that we got non-standard output
                Log::warning('mysqldump output does not contain standard SQL dump header, but proceeding anyway');
            }

            // Add directly to ZIP from string (more reliable)
            $zip->addFromString('database_'.$timestamp.'.sql', $output);
            Log::info('Database dump added to backup ('.$this->formatBytes(strlen($output)).')');

        } catch (\Exception $e) {
            Log::error('Database backup failed: '.$e->getMessage());
            // Don't fail the entire backup if database dump fails
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }

    /**
     * Get mysqldump binary path - tries bundled version first, then common locations
     */
    private function getMysqldumpPath(): string
    {
        // Try bundled binary in storage first
        $bundledPath = storage_path('app/binaries/mysqldump');
        if (file_exists($bundledPath)) {
            return $bundledPath;
        }

        // Try custom path from environment or config
        $customPath = env('MYSQLDUMP_PATH') ?: config('backup.mysqldump_path');
        if ($customPath && file_exists($customPath)) {
            return $customPath;
        }

        // Common paths for different systems
        $commonPaths = [
            '/usr/local/mysql-9.0.1-macos14-arm64/bin/mysqldump', // Herd on macOS ARM64
            '/usr/local/mysql/bin/mysqldump',                      // Herd default
            '/usr/bin/mysqldump',                                   // Linux default
            '/usr/local/bin/mysqldump',                             // macOS common
        ];

        foreach ($commonPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Fall back to system PATH
        return 'mysqldump';
    }
}
