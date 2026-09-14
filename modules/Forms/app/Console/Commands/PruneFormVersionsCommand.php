<?php

namespace Modules\Forms\Console\Commands;

use Illuminate\Console\Command;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormVersion;

class PruneFormVersionsCommand extends Command
{
    protected $signature = 'forms:prune-versions {--keep=10 : Número de versiones a conservar por formulario}';

    protected $description = 'Eliminar versiones antiguas de formularios, conservando las más recientes';

    public function handle(): int
    {
        $keep = (int) $this->option('keep');

        if ($keep < 1) {
            $this->error('El número de versiones a conservar debe ser mayor a 0');

            return self::FAILURE;
        }

        $totalDeleted = 0;
        $formsProcessed = 0;

        Form::query()
            ->has('versions')
            ->chunkById(100, function ($forms) use ($keep, &$totalDeleted, &$formsProcessed) {
                foreach ($forms as $form) {
                    $deleted = $this->pruneVersionsForForm($form->id, $keep);
                    $totalDeleted += $deleted;
                    $formsProcessed++;
                }
            });

        $this->info("Processed {$formsProcessed} forms, deleted {$totalDeleted} old versions (kept latest {$keep} per form)");

        return self::SUCCESS;
    }

    private function pruneVersionsForForm(int $formId, int $keep): int
    {
        $latestIds = FormVersion::query()
            ->where('form_id', $formId)
            ->orderByDesc('version_number')
            ->limit($keep)
            ->pluck('id');

        if ($latestIds->count() < $keep) {
            return 0;
        }

        return FormVersion::query()
            ->where('form_id', $formId)
            ->whereNotIn('id', $latestIds)
            ->delete();
    }
}
