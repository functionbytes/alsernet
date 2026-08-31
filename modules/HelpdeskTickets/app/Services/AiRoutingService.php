<?php

namespace Modules\HelpdeskTickets\Services;

use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketAssignment;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketHistory;

/**
 * Cierra el circuito entre lo que la IA deduce de un ticket y a quien acaba
 * llegando.
 *
 * No inventa un motor de asignacion nuevo: aplica la categoria que sugirio
 * ClassifyTicketJob (para que el ticket entre en la cola correcta) y delega el
 * reparto en AssignmentService, que ya sabe elegir por carga, habilidades y
 * hablantes del idioma detectado.
 *
 * Dos frenos deliberados:
 *
 *  - Nunca pisa una decision humana. Un ticket ya asignado, o con categoria
 *    puesta a mano, se deja como esta.
 *  - Por debajo del umbral de confianza el ticket se queda SIN asignar, en
 *    lugar de asignarse mal. Un ticket sin dueno se ve en la cola de
 *    pendientes; uno asignado al agente equivocado se queda ahi hasta que
 *    alguien se da cuenta.
 *
 * Todo lo que hace queda en el historial como `ai_routed`, con la confianza y
 * el motivo — igual que ya hace ClassifyTicketJob con `ai_classified`.
 */
class AiRoutingService
{
    public function __construct(
        private readonly AssignmentService $assignments,
    ) {}

    /**
     * @return array{applied: bool, reason: string, agent_id?: int, category_id?: int}
     */
    public function route(Ticket $ticket): array
    {
        if (! config('helpdeskagents.ticket_ai.routing.enabled', false)) {
            return $this->skip('routing_disabled');
        }

        if ($ticket->assignee_id) {
            return $this->skip('already_assigned');
        }

        $confidence = $this->confidence($ticket);
        $minConfidence = (float) config('helpdeskagents.ticket_ai.routing.min_confidence', 0.75);

        if ($confidence < $minConfidence) {
            // Sin senal fiable no se toca el ticket: se queda en la cola de
            // sin asignar, que es donde alguien lo va a ver.
            return $this->skip('low_confidence');
        }

        $appliedCategory = $this->applyCategory($ticket);

        $assignment = $this->assign($ticket);

        if ($assignment === null) {
            // La categoria si se aplico aunque no haya agente disponible: deja
            // el ticket en la cola correcta para el siguiente barrido.
            TicketHistory::logAction($ticket, 'ai_routed', null, [
                'automatic' => true,
                'confidence' => $confidence,
                'category_id' => $appliedCategory,
                'result' => 'no_agent_available',
            ]);

            return ['applied' => false, 'reason' => 'no_agent_available', 'category_id' => (int) $ticket->category_id];
        }

        TicketHistory::logAction($ticket, 'ai_routed', null, [
            'automatic' => true,
            'confidence' => $confidence,
            'category_id' => $appliedCategory,
            'agent_id' => $assignment->assigned_to,
            'strategy' => $this->strategy(),
            'detected_language' => $ticket->detected_language,
        ]);

        return [
            'applied' => true,
            'reason' => 'assigned',
            'agent_id' => (int) $assignment->assigned_to,
            'category_id' => (int) $ticket->category_id,
        ];
    }

    /**
     * Aplica la categoria sugerida si el ticket no tiene una puesta a mano.
     *
     * @return int|null id aplicado, o null si no se aplico ninguno
     */
    private function applyCategory(Ticket $ticket): ?int
    {
        $suggested = $ticket->ai_suggested_category_id;

        if ($ticket->category_id || ! $suggested) {
            return null;
        }

        // Lista cerrada: la categoria pudo borrarse entre la clasificacion y
        // el enrutado.
        if (! TicketCategory::query()->whereKey($suggested)->exists()) {
            return null;
        }

        $ticket->update([
            'category_id' => $suggested,
            'ai_suggested_category_id' => null,
        ]);

        return (int) $suggested;
    }

    private function assign(Ticket $ticket): ?TicketAssignment
    {
        try {
            // Las tres estrategias filtran ya por hablantes del idioma
            // detectado (AssignmentService::preferLanguageSpeakers), asi que
            // el enrutado por idioma sale gratis aqui.
            return match ($this->strategy()) {
                'round_robin' => $this->assignments->autoAssignByRoundRobin($ticket),
                'skills' => $this->assignments->autoAssignBySkills($ticket),
                default => $this->assignments->autoAssignByWorkload($ticket),
            };
        } catch (\Throwable $e) {
            Log::warning('AiRoutingService: fallo al asignar', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Confianza de la clasificacion.
     *
     * ClassifyTicketJob la deja en el historial como `ai_classified`; si no hay
     * entrada (clasificacion desactivada, o ticket anterior a la feature) se
     * asume 0, que es lo que impide enrutar a ciegas.
     */
    private function confidence(Ticket $ticket): float
    {
        $entry = TicketHistory::query()
            ->where('ticket_id', $ticket->id)
            ->where('action_type', 'ai_classified')
            ->latest('id')
            ->first();

        $value = data_get($entry?->metadata, 'confidence');

        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function strategy(): string
    {
        return (string) config('helpdeskagents.ticket_ai.routing.strategy', 'workload');
    }

    /**
     * @return array{applied: bool, reason: string}
     */
    private function skip(string $reason): array
    {
        return ['applied' => false, 'reason' => $reason];
    }
}
