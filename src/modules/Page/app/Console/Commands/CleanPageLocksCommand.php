<?php

namespace Modules\Page\Console\Commands;

use Illuminate\Console\Command;
use Modules\Page\Services\PageLockService;

class CleanPageLocksCommand extends Command
{
    protected $signature = 'page:clean-locks';

    protected $description = 'Limpiar locks de páginas expirados';

    public function handle(PageLockService $service): int
    {
        $count = $service->cleanExpiredLocks();

        $this->info("Limpiados {$count} locks expirados.");

        return self::SUCCESS;
    }
}
