<?php

namespace Modules\HelpdeskTranslate\Console\Commands;

use Illuminate\Console\Command;
use Modules\HelpdeskTranslate\Models\TranslationCache;

/**
 * Poda entradas de la caché de traducciones sin uso reciente.
 *
 * La tabla `helpdesk_translation_cache` almacena el texto original y traducido,
 * que puede contener PII del cliente. Como está deduplicada por hash (sin
 * customer_id), no puede purgarse por cliente en un borrado GDPR hard; en su
 * lugar se acota la retención podando lo que lleva `cache_retention_days` sin
 * usarse. Las frases recurrentes refrescan `last_used_at` en cada acierto, así
 * que sobreviven; las entradas únicas (más propensas a llevar PII) caducan.
 */
class PruneTranslationCacheCommand extends Command
{
    protected $signature = 'helpdesktranslate:prune-cache';

    protected $description = 'Poda la caché de traducciones sin uso reciente (retención GDPR de PII).';

    public function handle(): int
    {
        $retentionDays = (int) config('helpdesktranslate.cache_retention_days', 90);
        $cutoff = now()->subDays($retentionDays);

        $deleted = TranslationCache::query()
            ->where(function ($query) use ($cutoff): void {
                $query->where('last_used_at', '<', $cutoff)
                    ->orWhereNull('last_used_at');
            })
            ->delete();

        $this->info("Podadas {$deleted} entradas de la caché de traducciones anteriores a {$cutoff->toDateString()}.");

        return self::SUCCESS;
    }
}
