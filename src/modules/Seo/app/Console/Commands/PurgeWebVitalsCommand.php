<?php

namespace Modules\Seo\Console\Commands;

use Illuminate\Console\Command;
use Modules\Seo\Models\SeoWebVital;

class PurgeWebVitalsCommand extends Command
{
    protected $signature = 'seo:purge-web-vitals
        {--days= : Días a conservar (default: config Seo.web_vitals.retention_days)}
        {--dry-run : Mostrar cuántos se eliminarían sin borrar}';

    protected $description = 'Eliminar muestras antiguas de Core Web Vitals según retención configurada';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('Seo.web_vitals.retention_days', 90));

        if ($days <= 0) {
            $this->error('El parámetro days debe ser mayor que 0.');

            return self::INVALID;
        }

        $query = SeoWebVital::olderThan($days);
        $count = (int) $query->count();

        if ($count === 0) {
            $this->info("No hay muestras anteriores a {$days} días.");

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("[dry-run] Se eliminarían {$count} muestras anteriores a {$days} días.");

            return self::SUCCESS;
        }

        $deleted = $query->delete();
        $this->info("Eliminadas {$deleted} muestras anteriores a {$days} días.");

        return self::SUCCESS;
    }
}
