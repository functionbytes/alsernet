<?php

namespace Modules\HelpdeskTickets\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskTickets\Services\ArticleDraftService;
use Modules\HelpdeskTickets\Services\IncidentDetectionService;

/**
 * Propone artículos de ayuda a partir de tickets resueltos que se repiten.
 *
 * Reutiliza el agrupamiento de IncidentDetectionService con una ventana larga:
 * lo que ahí es «una caída ahora mismo» aquí es «una pregunta que llevamos un
 * mes respondiendo a mano».
 *
 * Pensado para correr semanalmente. Cada borrador queda sin publicar, para que
 * alguien lo revise: un artículo publicado sin revisar es documentación oficial
 * escrita por nadie.
 */
class SuggestHelpArticlesCommand extends Command
{
    protected $signature = 'helpdesk:suggest-articles
                            {--days=30 : Ventana de tickets a analizar}
                            {--min=4 : Tickets mínimos para proponer un artículo}
                            {--dry-run : Muestra los temas sin redactar nada}';

    protected $description = 'Propone borradores de artículos de ayuda a partir de tickets resueltos repetidos';

    /** Un mismo tema no se vuelve a proponer en este plazo. */
    private const PROPOSED_TTL_DAYS = 30;

    public function handle(IncidentDetectionService $detector, ArticleDraftService $drafts): int
    {
        if (! $drafts->isAvailable()) {
            $this->line('Borradores de artículo desactivados o sin centro de ayuda / agente IA.');

            return self::SUCCESS;
        }

        $days = max(1, (int) $this->option('days'));
        $min = max(2, (int) $this->option('min'));

        // Techo alto: el barrido mensual mira muchos más tickets que el aviso
        // de incidencias de la última hora.
        $groups = $detector->detect($days * 24 * 60, $min, 1500);

        if ($groups->isEmpty()) {
            $this->line('Sin temas recurrentes en la ventana analizada.');

            return self::SUCCESS;
        }

        $created = 0;

        foreach ($groups as $group) {
            $this->line(sprintf('%d tickets — %s', $group['size'], $group['label'] ?? 'sin título'));

            if ($this->option('dry-run')) {
                continue;
            }

            if ($this->alreadyProposed($group['ticket_ids'])) {
                $this->line('  (ya propuesto)');

                continue;
            }

            $article = $drafts->draftFrom($group['ticket_ids'], $group['label']);

            if ($article === null) {
                // Lo normal cuando el grupo comparte vocabulario pero no un
                // problema común: el servicio prefiere no escribir nada.
                $this->line('  (sin borrador: los casos no comparten un problema claro)');

                continue;
            }

            $this->info("  → borrador #{$article->id}: {$article->title}");
            $created++;
        }

        $this->info(sprintf('%d tema(s) analizado(s), %d borrador(es) creado(s).', $groups->count(), $created));

        return self::SUCCESS;
    }

    /**
     * Mismo criterio de huella que el aviso de incidencias: los tickets más
     * antiguos del grupo. Sin esto, el mismo tema generaría un borrador nuevo
     * cada semana y la papelera del centro de ayuda se llenaría de duplicados.
     *
     * @param  array<int, int>  $ticketIds
     */
    private function alreadyProposed(array $ticketIds): bool
    {
        sort($ticketIds);
        $key = 'helpdesktickets:article-draft:'.md5(implode(',', array_slice($ticketIds, 0, 5)));

        if (Cache::has($key)) {
            return true;
        }

        Cache::put($key, true, now()->addDays(self::PROPOSED_TTL_DAYS));

        return false;
    }
}
