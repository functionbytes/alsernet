<?php

namespace Modules\Backup\Console\Commands;

use Illuminate\Console\Command;
use Modules\Backup\Jobs\CreateBackupJob;
use Modules\Backup\Models\BackupSchedule;
use Modules\Core\Models\Setting;

class RunScheduledBackups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:run-scheduled-backups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run scheduled backups that are due';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $schedules = BackupSchedule::where('enabled', true)->get();

        if ($schedules->isEmpty()) {
            $this->info('No backup schedules found.');

            return Command::SUCCESS;
        }

        $executed = 0;

        foreach ($schedules as $schedule) {
            if ($schedule->shouldRunNow()) {
                $this->executeBackup($schedule);
                $schedule->markAsRun();
                $executed++;

                $this->info("✓ Backup '{$schedule->name}' executed successfully");
            }
        }

        if ($executed === 0) {
            $this->info('No schedules are due to run at this time.');
        } else {
            $this->info("Total backups executed: {$executed}");
        }

        return Command::SUCCESS;
    }

    /**
     * Execute a backup for the given schedule
     */
    private function executeBackup(BackupSchedule $schedule): void
    {
        $backupTypes = $schedule->backup_types ?? [];

        if (empty($backupTypes)) {
            $this->warn("Schedule '{$schedule->name}' has no backup types selected");

            return;
        }

        // Build include paths based on selection
        $includePaths = [];
        $typeMap = [
            'app_code' => base_path('app'),
            'config' => base_path('config'),
            'routes' => base_path('routes'),
            'resources' => base_path('resources'),
            'migrations' => base_path('database/migrations'),
            'storage' => base_path('storage/app'),
        ];

        foreach ($backupTypes as $type) {
            if ($type === 'database') {
                continue;
            }
            if (isset($typeMap[$type])) {
                $includePaths[] = $typeMap[$type];
            }
        }

        // Create backup config
        $backupConfig = [
            'files' => [
                'include' => $includePaths,
                'exclude' => config('backup.backup.source.files.exclude', []),
                'follow_links' => false,
                'relative_path' => null,
            ],
            'databases' => [],
        ];

        // Get database backups if database backup is selected
        $dbSettings = null;
        if (in_array('database', $backupTypes)) {
            $dbSettings = Setting::getDatabaseSettings();
        }

        // Dispatch the backup job
        CreateBackupJob::dispatch($backupTypes, $backupConfig, $dbSettings);
    }
}
