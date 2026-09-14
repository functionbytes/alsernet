<?php

namespace Modules\Mailer\Console\Commands;

use Illuminate\Console\Command;
use Modules\Mailer\Models\MailerTemplateVersion;

class PurgeMailerVersionsCommand extends Command
{
    protected $signature = 'mailer:purge-versions {--keep= : Versiones a mantener por template (default: config mailer-module.retention.versions_per_template)} {--dry-run : No eliminar, solo mostrar cuántas se eliminarían}';

    protected $description = 'Conservar solo las últimas N versiones por plantilla+idioma';

    public function handle(): int
    {
        $keep = (int) ($this->option('keep') ?? config('mailer-module.retention.versions_per_template', 50));

        if ($keep <= 0) {
            $this->error('El parámetro keep debe ser mayor que 0.');

            return self::INVALID;
        }

        $dryRun = (bool) $this->option('dry-run');

        $toDelete = MailerTemplateVersion::query()
            ->select('id')
            ->fromSub(function ($sub) {
                $sub->from('mailer_template_versions')
                    ->select('id')
                    ->selectRaw('ROW_NUMBER() OVER (PARTITION BY mailer_template_id, lang_id ORDER BY id DESC) as rn');
            }, 'ranked')
            ->where('rn', '>', $keep)
            ->pluck('id');

        if ($toDelete->isEmpty()) {
            $this->info('No hay versiones para eliminar.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info('[dry-run] Se eliminarían '.$toDelete->count().' versiones (manteniendo '.$keep.' por template+idioma).');

            return self::SUCCESS;
        }

        $deleted = MailerTemplateVersion::whereIn('id', $toDelete)->delete();

        $this->info("Eliminadas {$deleted} versiones (manteniendo {$keep} por template+idioma).");

        return self::SUCCESS;
    }
}
