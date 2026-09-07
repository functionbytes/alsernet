<?php

namespace Modules\HelpdeskTickets\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Notifications\TicketIncidentDetected;
use Modules\HelpdeskTickets\Services\IncidentDetectionService;

/**
 * Avisa cuando un grupo de tickets recientes trata del mismo problema.
 *
 * Pensado para el scheduler, cada pocos minutos. Lo que aporta no es detectar
 * el problema —eso lo hace el primer agente que lee el ticket— sino detectar
 * que es MASIVO antes de que quince personas lo investiguen por separado.
 *
 * Anti-repetición: un mismo grupo se avisa una vez. Sin esto, una incidencia
 * que dura tres horas dispara un aviso en cada pasada y el equipo aprende a
 * ignorarlos, que es la única forma de que esto deje de funcionar.
 */
class DetectTicketIncidentsCommand extends Command
{
    protected $signature = 'helpdesk:detect-incidents {--dry-run : Muestra los grupos sin notificar}';

    protected $description = 'Detecta picos de tickets sobre un mismo problema y avisa al equipo';

    /** Un grupo ya avisado no se repite durante esta ventana. */
    private const NOTIFIED_TTL_MINUTES = 180;

    public function handle(IncidentDetectionService $detector): int
    {
        if (! config('helpdeskagents.ticket_similarity.enabled', false)) {
            $this->line('Similitud entre tickets desactivada (helpdeskagents.ticket_similarity.enabled).');

            return self::SUCCESS;
        }

        $groups = $detector->detect();

        if ($groups->isEmpty()) {
            $this->line('Sin picos detectados.');

            return self::SUCCESS;
        }

        $notified = 0;

        foreach ($groups as $group) {
            $this->line(sprintf(
                '%d tickets — %s (desde %s)',
                $group['size'],
                $group['label'] ?? 'sin título',
                $group['first_seen'],
            ));

            if ($this->option('dry-run')) {
                continue;
            }

            if ($this->alreadyNotified($group['ticket_ids'])) {
                $this->line('  (ya avisado)');

                continue;
            }

            $this->notify($group);
            $notified++;
        }

        if ($notified > 0) {
            Log::info('Helpdesk: incidencias masivas detectadas', ['groups' => $notified]);
        }

        $this->info(sprintf('%d grupo(s) detectado(s), %d aviso(s) enviado(s).', $groups->count(), $notified));

        return self::SUCCESS;
    }

    /**
     * La huella es el conjunto de tickets del grupo, no su título: el mismo
     * incidente puede describirse distinto en dos pasadas mientras siguen
     * llegando tickets, pero comparte el núcleo de ids.
     *
     * @param  array<int, int>  $ticketIds
     */
    private function alreadyNotified(array $ticketIds): bool
    {
        sort($ticketIds);

        // Solo los más antiguos del grupo entran en la huella: los que van
        // llegando después no deben convertirlo en un incidente "nuevo".
        $core = array_slice($ticketIds, 0, 5);
        $key = 'helpdesktickets:incident:notified:'.md5(implode(',', $core));

        if (Cache::has($key)) {
            return true;
        }

        Cache::put($key, true, now()->addMinutes(self::NOTIFIED_TTL_MINUTES));

        return false;
    }

    /**
     * @param  array<string, mixed>  $group
     */
    private function notify(array $group): void
    {
        $recipients = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['helpdesk-manager', 'helpdesk-admin', 'manager']))
            ->get();

        if ($recipients->isEmpty()) {
            $this->warn('  Sin destinatarios con rol de gestión: no se avisa.');

            return;
        }

        $sample = Ticket::query()
            ->whereIn('id', array_slice($group['ticket_ids'], 0, 5))
            ->get(['id', 'ticket_number', 'subject']);

        foreach ($recipients as $user) {
            $user->notify(new TicketIncidentDetected(
                $group['label'] ?? 'Varios tickets sobre el mismo problema',
                $group['size'],
                $sample,
            ));
        }
    }
}
