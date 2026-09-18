<?php

namespace Modules\PriceLabels\Console\Commands;

use Illuminate\Console\Command;
use Modules\PriceLabels\Services\PriceLabelGenerationService;

class PruneOldPriceLabelGenerationsCommand extends Command
{
    protected $signature = 'pricelabels:prune-generations';

    protected $description = 'Elimina las generaciones de PDF de etiquetas de precio mas antiguas que el periodo de retencion configurado';

    public function handle(PriceLabelGenerationService $generationService): int
    {
        $days = (int) config('pricelabels.generation_retention_days', 90);

        $deleted = $generationService->pruneOlderThan($days);

        $this->info("Eliminadas {$deleted} generacion(es) con mas de {$days} dias de antiguedad.");

        return self::SUCCESS;
    }
}
