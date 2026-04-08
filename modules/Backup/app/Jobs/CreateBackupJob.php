<?php

namespace Modules\Backup\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Backup\Mail\BackupMail;
use Modules\Core\Models\Setting;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Services\MailerTemplateRendererService;
use ZipArchive;

class CreateBackupJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 600;

    public int $maxExceptions = 2;

    public int $uniqueFor = 3600;

    /**
     * Backoff strategy: 2min, 10min (backup jobs are resource-heavy, wait longer)
     */
    public function backoff(): array
    {
        return [120, 600];
    }

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
        private array $backupTypes,
        private array $backupConfig,
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

    private function getEmailsForEvent(string $event): array
    {
        $enabledKey = $event === 'success' ? 'backup_notification_success_enabled' : 'backup_notification_failed_enabled';
        $emailsKey = $event === 'success' ? 'backup_notification_success_emails' : 'backup_notification_failed_emails';

        if (! Setting::get($enabledKey, $event === 'failed' ? '1' : '0')) {
            return [];
        }

        $emails = Setting::get($emailsKey, '[]');
        $emails = is_string($emails) ? json_decode($emails, true) : $emails;

        return (is_array($emails) && ! empty($emails))
            ? $emails
            : array_filter([config('backup.notifications.mail.to')]);
    }

    private function notifySuccess(string $size): void
    {
        $emails = $this->getEmailsForEvent('success');

        if (! empty($emails)) {
            $variables = [
                'APP_NAME' => config('app.name'),
                'BACKUP_SIZE' => $size,
                'BACKUP_DATE' => now()->format('d/m/Y H:i'),
                'BACKUP_URL' => route('settings.backups.index'),
            ];

            [$subject, $html] = $this->renderTemplate('BACKUP_SUCCESS', $variables,
                'Backup completado: '.config('app.name')
            );

            foreach ($emails as $email) {
                try {
                    Mail::to($email)->send(new BackupMail($subject, $html));
                    Log::info('Backup success notification sent to: '.$email);
                } catch (\Exception $e) {
                    Log::warning('Could not send backup success notification: '.$e->getMessage());
                }
            }
        }

        $slackWebhook = config('backup.notifications.slack.webhook_url');
        if (! empty($slackWebhook)) {
            try {
                Http::post($slackWebhook, [
                    'text' => "✅ Backup completado: {$size} — ".now()->format('Y-m-d H:i'),
                ]);
            } catch (\Exception $e) {
                Log::warning('Slack backup notification failed: '.$e->getMessage());
            }
        }

        $discordWebhook = config('backup.notifications.discord.webhook_url');
        if (! empty($discordWebhook)) {
            try {
                Http::post($discordWebhook, [
                    'content' => "✅ Backup completado: {$size} — ".now()->format('Y-m-d H:i'),
                ]);
            } catch (\Exception $e) {
                Log::warning('Discord backup notification failed: '.$e->getMessage());
            }
        }
    }

    private function notifyFailure(string $errorMessage): void
    {
        $emails = $this->getEmailsForEvent('failed');

        if (! empty($emails)) {
            $variables = [
                'APP_NAME' => config('app.name'),
                'BACKUP_DATE' => now()->format('d/m/Y H:i'),
                'BACKUP_URL' => route('settings.backups.index'),
                'ERROR_MESSAGE' => $errorMessage,
            ];

            [$subject, $html] = $this->renderTemplate('BACKUP_FAILED', $variables,
                'Error en backup: '.config('app.name')
            );

            foreach ($emails as $email) {
                try {
                    Mail::to($email)->send(new BackupMail($subject, $html));
                    Log::info('Backup failure notification sent to: '.$email);
                } catch (\Exception $e) {
                    Log::warning('Could not send backup failure notification: '.$e->getMessage());
                }
            }
        }

        $slackWebhook = config('backup.notifications.slack.webhook_url');
        if (! empty($slackWebhook)) {
            try {
                Http::post($slackWebhook, [
                    'text' => "❌ Backup falló: {$errorMessage}",
                ]);
            } catch (\Exception $e) {
                Log::warning('Slack backup failure notification failed: '.$e->getMessage());
            }
        }

        $discordWebhook = config('backup.notifications.discord.webhook_url');
        if (! empty($discordWebhook)) {
            try {
                Http::post($discordWebhook, [
                    'content' => "❌ Backup falló: {$errorMessage}",
                ]);
            } catch (\Exception $e) {
                Log::warning('Discord backup failure notification failed: '.$e->getMessage());
            }
        }
    }

    /**
     * Render a mailer template by key, returning [subject, html].
     * Falls back to a plain subject + empty HTML if template not found.
     *
     * @return array{0: string, 1: string}
     */
    private function renderTemplate(string $key, array $variables, string $fallbackSubject): array
    {
        $template = MailerTemplate::where('key', $key)->where('is_enabled', true)->first();

        if (! $template) {
            Log::warning("Backup notification template '{$key}' not found, sending plain email.");

            return [$fallbackSubject, ''];
        }

        $translation = $template->translate(1);
        $subject = $translation?->subject
            ? MailerTemplateRendererService::replaceVariables($translation->subject, $variables)
            : $fallbackSubject;

        $html = MailerTemplateRendererService::renderEmailTemplate($template, $variables, 1);

        return [$subject, $html];
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
