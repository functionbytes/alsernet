<?php

namespace Modules\HelpdeskBirthday\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Modules\HelpdeskBirthday\Services\BirthdayRedemptionSyncService;

/**
 * Trae de la tienda los bonos que se han gastado.
 *
 * El scheduler lo corre cada hora sobre los últimos días, que es donde puede
 * cambiar algo. `--days=` sirve para el retroceso inicial: con --days=1400 se
 * carga todo el histórico de la tienda, que es la línea base contra la que
 * comparar una campaña.
 */
class SyncBirthdayRedemptions extends Command
{
    protected $signature = 'helpdeskbirthday:sync-redemptions
                            {--days= : Días hacia atrás a sincronizar (por defecto 45)}
                            {--from= : Fecha inicial YYYY-MM-DD, manda sobre --days}
                            {--to= : Fecha final YYYY-MM-DD}
                            {--all : Todo el histórico, sin acotar por fecha}
                            {--name= : Filtro por nombre del cupón en la tienda}';

    protected $description = 'Sincroniza los canjes de bono de PrestaShop con la copia local';

    public function handle(BirthdayRedemptionSyncService $sync): int
    {
        if (! $sync->isAvailable()) {
            $this->error('No hay de dónde leer los canjes: revisa el bridge o HELPDESK_PS_DB.');

            return self::FAILURE;
        }

        [$from, $to] = $this->range();

        $this->info($from
            ? "Sincronizando canjes desde {$from->toDateString()}".($to ? " hasta {$to->toDateString()}" : '').'…'
            : 'Sincronizando TODO el histórico de canjes…');

        $result = $sync->sync($from, $to, $this->option('name'));

        $this->info(sprintf(
            'Leídos %d · guardados %d · atribuidos %d (fuente: %s)',
            $result['read'],
            $result['saved'],
            $result['attributed'],
            $result['source'],
        ));

        return self::SUCCESS;
    }

    /**
     * @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable}
     */
    private function range(): array
    {
        if ($this->option('all')) {
            return [null, null];
        }

        $to = $this->option('to') ? CarbonImmutable::parse($this->option('to')) : null;

        if ($this->option('from')) {
            return [CarbonImmutable::parse($this->option('from')), $to];
        }

        $days = (int) ($this->option('days') ?: 45);

        return [CarbonImmutable::today()->subDays($days), $to];
    }
}
