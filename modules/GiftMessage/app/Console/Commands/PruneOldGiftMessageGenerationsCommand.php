<?php

namespace Modules\GiftMessage\Console\Commands;

use Illuminate\Console\Command;
use Modules\GiftMessage\Services\GiftMessageGenerationService;

class PruneOldGiftMessageGenerationsCommand extends Command
{
    protected $signature = 'giftmessage:prune-generations';

    protected $description = 'Elimina las generaciones de PDF de mensajes regalo mas antiguas que el periodo de retencion configurado';

    public function handle(GiftMessageGenerationService $generationService): int
    {
        $days = (int) config('giftmessage.generation_retention_days', 90);

        $deleted = $generationService->pruneOlderThan($days);

        $this->info("Eliminadas {$deleted} generacion(es) con mas de {$days} dias de antiguedad.");

        return self::SUCCESS;
    }
}
