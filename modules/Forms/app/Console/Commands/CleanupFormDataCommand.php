<?php

namespace Modules\Forms\Console\Commands;

use Illuminate\Console\Command;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormAbandonTracking;

class CleanupFormDataCommand extends Command
{
    protected $signature = 'forms:cleanup {--dry-run : Solo muestra lo que se eliminaría}';

    protected $description = 'Elimina submissions antiguas según política de retención de datos (GDPR)';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $totalDeleted = 0;

        $forms = Form::query()
            ->whereNotNull('retention_days')
            ->where('retention_days', '>', 0)
            ->get();

        foreach ($forms as $form) {
            $cutoff = now()->subDays($form->retention_days);
            $query = $form->submissions()->where('created_at', '<', $cutoff);
            $count = $query->count();

            if ($count === 0) {
                continue;
            }

            $this->info("Form '{$form->name}': {$count} submissions para eliminar (anteriores a {$cutoff->format('d/m/Y')})");

            if (! $isDryRun) {
                $query->delete();
                $totalDeleted += $count;
            }
        }

        $abandonCutoff = now()->subDays(config('forms.abandon_tracking_ttl_days', 30));
        $abandonCount = FormAbandonTracking::query()
            ->where('last_activity_at', '<', $abandonCutoff)
            ->count();

        if (! $isDryRun) {
            FormAbandonTracking::query()
                ->where('last_activity_at', '<', $abandonCutoff)
                ->delete();
        }

        $this->info("Total submissions eliminadas: {$totalDeleted}");
        $this->info("Abandon tracking eliminado: {$abandonCount}");

        return Command::SUCCESS;
    }
}
