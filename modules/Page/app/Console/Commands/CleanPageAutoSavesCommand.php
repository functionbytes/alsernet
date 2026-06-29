<?php

namespace Modules\Page\Console\Commands;

use Illuminate\Console\Command;
use Modules\Page\Services\PageAutoSaveService;

class CleanPageAutoSavesCommand extends Command
{
    protected $signature = 'page:clean-auto-saves';

    protected $description = 'Limpiar borradores automáticos expirados';

    public function handle(PageAutoSaveService $service): int
    {
        $count = $service->cleanExpiredDrafts();

        $this->info("Limpiados {$count} borradores expirados.");

        return self::SUCCESS;
    }
}
