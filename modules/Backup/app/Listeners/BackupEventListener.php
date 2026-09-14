<?php

namespace Modules\Backup\Listeners;

use Spatie\Backup\Events\BackupHasFailed;
use Spatie\Backup\Events\BackupHasSucceeded;
use Spatie\Backup\Events\BackupWasNotSuccessful;
use Spatie\Backup\Events\BackupWasSuccessful;
use Spatie\Backup\Events\CleanupHasFailed;
use Spatie\Backup\Events\CleanupWasSuccessful;
use Spatie\Backup\Events\HealthyBackupWasFound;
use Spatie\Backup\Events\UnhealthyBackupWasFound;

/**
 * Silent listener for backup events.
 *
 * Handles all backup events from Spatie Laravel Backup package
 * without throwing errors or sending notifications.
 * This prevents "There is no notification class that can handle event" errors.
 */
class BackupEventListener
{
    /**
     * Handle backup successful event
     */
    public function handleBackupHasSucceeded(BackupHasSucceeded $event): void
    {
        // Log backup success
        \Log::info('Backup created successfully: '.$event->backupDestination->disk().' disk');
    }

    /**
     * Handle backup failed event
     */
    public function handleBackupHasFailed(BackupHasFailed $event): void
    {
        // Log backup failure
        \Log::error('Backup failed: '.$event->exception->getMessage());
    }

    /**
     * Handle backup was successful event
     */
    public function handleBackupWasSuccessful(BackupWasSuccessful $event): void
    {
        // Log backup completion
        \Log::info('Backup process completed successfully');
    }

    /**
     * Handle backup was not successful event
     */
    public function handleBackupWasNotSuccessful(BackupWasNotSuccessful $event): void
    {
        // Log backup completion error
        \Log::error('Backup process had errors: '.$event->exception->getMessage());
    }

    /**
     * Handle backup successful event
     */
    public function onBackupWasSuccessful(BackupWasSuccessful $event)
    {
        \Log::info('Backup was successful', [
            'backup' => $event->backupDestination->backupName(),
        ]);
    }

    /**
     * Handle backup failed event
     */
    public function onBackupHasFailed(BackupHasFailed $event)
    {
        \Log::error('Backup has failed', [
            'exception' => $event->exception->getMessage(),
        ]);
    }

    /**
     * Handle healthy backup found event
     */
    public function onHealthyBackupWasFound(HealthyBackupWasFound $event)
    {
        \Log::info('Healthy backup was found');
    }

    /**
     * Handle unhealthy backup found event
     */
    public function onUnhealthyBackupWasFound(UnhealthyBackupWasFound $event)
    {
        \Log::warning('Unhealthy backup was found', [
            'issues' => $event->issues,
        ]);
    }

    /**
     * Handle cleanup successful event
     */
    public function onCleanupWasSuccessful(CleanupWasSuccessful $event)
    {
        \Log::info('Backup cleanup was successful');
    }

    /**
     * Handle cleanup failed event
     */
    public function onCleanupHasFailed(CleanupHasFailed $event)
    {
        \Log::error('Backup cleanup has failed', [
            'exception' => $event->exception->getMessage(),
        ]);
    }
}
