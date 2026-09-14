<?php

namespace Modules\Forms\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Forms\Models\FormAbandonTracking;
use Modules\Forms\Models\FormSubmission;

class CleanupAbandonedFormsCommand extends Command
{
    protected $signature = 'forms:cleanup-abandoned {--days=30 : Días de antigüedad para purgar registros}';

    protected $description = 'Eliminar registros de abandon tracking y submissions incompletas antiguas';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->error('El número de días debe ser mayor a 0');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);

        $abandonDeleted = $this->pruneAbandonTracking($cutoff);
        $submissionsDeleted = $this->pruneDraftSubmissions($cutoff);

        $this->info("Deleted {$abandonDeleted} abandon tracking records and {$submissionsDeleted} incomplete submissions older than {$days} days");

        return self::SUCCESS;
    }

    private function pruneAbandonTracking(Carbon $cutoff): int
    {
        $deleted = 0;

        FormAbandonTracking::query()
            ->where('last_activity_at', '<', $cutoff)
            ->chunkById(500, function ($records) use (&$deleted) {
                foreach ($records as $record) {
                    $record->delete();
                    $deleted++;
                }
            });

        return $deleted;
    }

    private function pruneDraftSubmissions(Carbon $cutoff): int
    {
        $deleted = 0;

        // form_submissions uses enum('new','in_review','resolved','rejected') — no draft status exists
        // Instead prune unread, unresolved new submissions older than cutoff (conservative cleanup)
        FormSubmission::query()
            ->where('status', 'new')
            ->where('is_read', false)
            ->where('created_at', '<', $cutoff)
            ->chunkById(500, function ($records) use (&$deleted) {
                foreach ($records as $record) {
                    $record->delete();
                    $deleted++;
                }
            });

        return $deleted;
    }
}
