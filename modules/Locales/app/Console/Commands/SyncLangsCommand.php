<?php

namespace Modules\Locales\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Locales\Models\Locale;
use Modules\Locales\Services\LocaleService;

/**
 * Sincroniza la tabla legacy `langs.available` con `locales.is_active`
 * cruzando por `iso_code == code`.
 *
 * Idempotente: puede correrse en cada deploy. El Observer ya mantiene
 * el sync fila-a-fila cuando se edita un Locale; este comando cubre
 * cambios manuales en la BD o el primer arranque tras instalar Locales.
 */
class SyncLangsCommand extends Command
{
    protected $signature = 'locales:sync-langs {--dry-run : Mostrar diferencias sin aplicarlas}';

    protected $description = 'Sincroniza langs.available con locales.is_active (soporta soft-deleted)';

    public function handle(): int
    {
        if (! Schema::hasTable('langs')) {
            $this->warn('La tabla langs no existe; nada que sincronizar.');

            return self::SUCCESS;
        }

        $locales = Locale::withTrashed()->get(['id', 'code', 'is_active', 'deleted_at']);

        if ($locales->isEmpty()) {
            $this->warn('No hay rows en locales; nada que sincronizar.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $changes = [];

        foreach ($locales as $locale) {
            $expected = $locale->trashed() ? 0 : ($locale->is_active ? 1 : 0);

            $current = DB::table('langs')->where('iso_code', $locale->code)->value('available');

            if ($current === null) {
                continue; // no hay row correspondiente en langs (nada que hacer)
            }

            if ((int) $current !== $expected) {
                $changes[] = [$locale->code, (int) $current, $expected];

                if (! $dryRun) {
                    DB::table('langs')
                        ->where('iso_code', $locale->code)
                        ->update(['available' => $expected]);
                }
            }
        }

        if (empty($changes)) {
            $this->info('langs.available ya está sincronizado con locales.is_active.');

            return self::SUCCESS;
        }

        $this->table(['code', 'langs.available (antes)', 'langs.available (después)'], $changes);
        $this->info(($dryRun ? '[dry-run] ' : '').count($changes).' filas '.($dryRun ? 'se actualizarían' : 'actualizadas').'.');

        if (! $dryRun) {
            Locale::clearResolvedLegacyLangId();
            LocaleService::clearCache();
        }

        return self::SUCCESS;
    }
}
