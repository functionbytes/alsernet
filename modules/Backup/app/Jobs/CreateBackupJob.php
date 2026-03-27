<?php

namespace Modules\Backup\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Backup\Notifications\BackupFailedNotification;
use Modules\Backup\Notifications\BackupSuccessfulNotification;
use ZipArchive;

class CreateBackupJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 600;

    public int $maxExceptions = 2;

    public int $uniqueFor = 3600;

    public function uniqueId(): string
    {
        return 'backup-'.implode('-', $this->backupTypes);
    }

    /**
     * Create a new job instance.
     *
     * @param  array<string>  $backupTypes  Types of backup to perform (e.g. 'database', 'app_code')
     * @param  array<string, mixed>  $backupConfig  File paths and database connection names to include
     */
    public function __construct(
        private readonly array $backupTypes,
        private readonly array $backupConfig,
    ) {}

    /**
     * Execute the job — create selective backups using PHP's ZipArchive.
     * Credentials are read fresh from config at runtime, never serialized.
     */
    public function handle(): void
    {
        ini_set('memory_limit', '512M');

        try {
            Log::info('Starting backup job with types: '.implode(', ', $this->backupTypes));
            Log::info('Files to backup: '.json_encode($this->backupConfig['files']['include'] ?? []));

            $backupDir = storage_path('app/'.config('app.name', 'backup'));
            if (! is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $timestamp = date('Y-m-d-H-i-s');
            $backupFile = $backupDir.'/'.$timestamp.'.zip';

            $zip = new ZipArchive;
            if ($zip->open($backupFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('Cannot create backup file: '.$backupFile);
            }

            $filesToBackup = $this->backupConfig['files']['include'] ?? [];
            foreach ($filesToBackup as $path) {
                if (file_exists($path)) {
                    $this->addPathToZip($zip, $path, '');
                    Log::info('Added to backup: '.$path);
                }
            }

            if (in_array('database', $this->backupTypes)) {
                $this->addDatabaseDumpToZip($zip, $timestamp);
            }

            $zip->close();

            $readableSize = backup_format_bytes(filesize($backupFile));
            Log::info('Backup file created: '.basename($backupFile).' ('.$readableSize.')');

            $this->notifySuccess($readableSize);
        } catch (\Exception $e) {
            Log::error('Backup job failed: '.$e->getMessage());
            $this->notifyFailure($e->getMessage());
            throw $e;
        }
    }

    private function notifySuccess(string $size): void
    {
        $email = config('backup.notifications.mail.to');

        if (empty($email)) {
            return;
        }

        try {
            Notification::route('mail', $email)
                ->notify(new BackupSuccessfulNotification($size));
        } catch (\Exception $e) {
            Log::warning('Could not send backup success notification: '.$e->getMessage());
        }
    }

    private function notifyFailure(string $errorMessage): void
    {
        $email = config('backup.notifications.mail.to');

        if (empty($email)) {
            return;
        }

        try {
            Notification::route('mail', $email)
                ->notify(new BackupFailedNotification($errorMessage));
        } catch (\Exception $e) {
            Log::warning('Could not send backup failure notification: '.$e->getMessage());
        }
    }

    /**
     * Recursively add a path (file or directory) to the ZIP archive.
     */
    private function addPathToZip(ZipArchive $zip, string $path, string $arcPath): void
    {
        if (! file_exists($path)) {
            return;
        }

        $baseName = basename($path);

        if (is_dir($path)) {
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

            return;
        }

        $zipPath = $arcPath ? $arcPath.'/'.$baseName : $baseName;
        $zip->addFile($path, $zipPath);
    }

    /**
     * Create and add database dump to ZIP.
     * Credentials are read fresh from config — never stored in the job payload.
     */
    private function addDatabaseDumpToZip(ZipArchive $zip, string $timestamp): void
    {
        try {
            // Read credentials from config at runtime, not from serialized job data
            $connection = config('database.default', 'mysql');
            $dbHost = config("database.connections.{$connection}.host", 'localhost');
            $dbUser = config("database.connections.{$connection}.username");
            $dbPass = config("database.connections.{$connection}.password", '');
            $dbName = config("database.connections.{$connection}.database");
            $dbPort = config("database.connections.{$connection}.port", 3306);

            if (empty($dbName) || empty($dbUser)) {
                Log::warning('Database credentials not configured, skipping database backup');

                return;
            }

            Log::info('Creating database dump for: '.$dbName.'@'.$dbHost);

            $mysqldumpPath = $this->getMysqldumpPath();

            $command = [
                $mysqldumpPath,
                '-h', $dbHost,
                '-P', (string) $dbPort,
                '-u', $dbUser,
                '-p'.($dbPass ?? ''),
                $dbName,
            ];

            $env = array_merge($_ENV, ['MYSQL_PWD' => $dbPass ?? '']);
            $cmdStr = implode(' ', array_map('escapeshellarg', $command));

            Log::info('Executing mysqldump command: '.substr($cmdStr, 0, 100).'...');

            $pipes = [];
            $process = proc_open(
                $cmdStr,
                [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ],
                $pipes,
                null,
                $env
            );

            if (! is_resource($process)) {
                Log::error('Failed to execute mysqldump');

                return;
            }

            fclose($pipes[0]);

            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $errors = stream_get_contents($pipes[2]);
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

            if (! preg_match('/--\s*(MySQL|MariaDB)\s+dump/i', $output)) {
                if (preg_match('/command not found|Connection refused|Access denied|Unknown database/i', $output)) {
                    Log::error('mysqldump error detected: '.substr($output, 0, 200));

                    return;
                }
                Log::warning('mysqldump output does not contain standard SQL dump header, but proceeding anyway');
            }

            $zip->addFromString('database_'.$timestamp.'.sql', $output);
            Log::info('Database dump added to backup ('.backup_format_bytes(strlen($output)).')');
        } catch (\Exception $e) {
            Log::error('Database backup failed: '.$e->getMessage());
        }
    }

    /**
     * Get mysqldump binary path — tries bundled version first, then common locations.
     */
    private function getMysqldumpPath(): string
    {
        $bundledPath = storage_path('app/binaries/mysqldump');
        if (file_exists($bundledPath)) {
            return $bundledPath;
        }

        $customPath = config('backup.mysqldump_path');
        if ($customPath && file_exists($customPath)) {
            return $customPath;
        }

        $commonPaths = [
            '/usr/local/mysql-9.0.1-macos14-arm64/bin/mysqldump',
            '/usr/local/mysql/bin/mysqldump',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
        ];

        foreach ($commonPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return 'mysqldump';
    }

    /**
     * Handle job final failure after all retries are exhausted.
     */
    public function failed(\Throwable $e): void
    {
        Log::error('Backup job failed after all retries: '.$e->getMessage());
        $this->notifyFailure($e->getMessage());
    }
}
