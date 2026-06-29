<?php

namespace Modules\Optimize\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Modules\Optimize\Http\Controllers\OptimizeController;

/**
 * Purga todos los caches del framework + los del módulo Optimize + opcache.
 * Ejecutar después de deployar para garantizar que las nuevas versiones de
 * CSS/JS/views se sirvan inmediatamente.
 */
class PurgeCacheCommand extends Command
{
    protected $signature = 'optimize:purge-cache';

    protected $description = 'Purga todos los caches: view, route, config, compiled, opcache y el response cache de Optimize';

    public function handle(): int
    {
        $this->info('▸ Laravel caches…');
        foreach (['view:clear', 'route:clear', 'config:clear', 'cache:clear'] as $cmd) {
            Artisan::call($cmd);
            $this->line('  ✔ '.$cmd);
        }

        $this->info('▸ Optimize stats & response cache…');
        Cache::forget('optimize.stats.requests');
        Cache::forget('optimize.stats.bytes_saved');
        OptimizeController::flushResponseCache();
        $this->line('  ✔ response cache tag + stats');

        if (function_exists('opcache_reset')) {
            $ok = @opcache_reset();
            $this->line($ok ? '  ✔ opcache_reset()' : '  · opcache no disponible');
        } else {
            $this->line('  · opcache_reset no disponible');
        }

        $this->newLine();
        $this->info('✔ Caches purgados.');

        return self::SUCCESS;
    }
}
