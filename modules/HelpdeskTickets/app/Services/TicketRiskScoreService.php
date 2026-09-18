<?php

namespace Modules\HelpdeskTickets\Services;

use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketHistory;

/**
 * Riesgo de que un ticket acabe mal.
 *
 * `EscalationService` escala por reloj: 48, 24 o 12 horas según la prioridad, y
 * nada más. Eso trata igual al ticket de un cliente tranquilo que lleva un día
 * esperando y al de uno furioso que ya ha reabierto el caso dos veces.
 *
 * Este servicio pondera señales que el sistema YA calcula y no miraba juntas.
 * No hace ninguna llamada al modelo: el sentimiento se lo dio el LLM cuando el
 * ticket entró, y aquí solo se combina. Es aritmética, así que además es
 * explicable — cada factor viene con su motivo, y el agente puede discutirlo.
 *
 * El resultado NO escala por sí solo: acorta el plazo que aplica
 * EscalationService. Un ticket de riesgo alto escala antes; uno tranquilo,
 * cuando le toca.
 */
class TicketRiskScoreService
{
    /**
     * Riesgo de 0 (ninguno) a 1 (máximo), con los factores que lo componen.
     *
     * @return array{score: float, factors: array<int, array{key: string, weight: float, detail: string}>}
     */
    public function score(Ticket $ticket): array
    {
        $factors = array_values(array_filter([
            $this->sentimentFactor($ticket),
            $this->reopenFactor($ticket),
            $this->slaFactor($ticket),
            $this->unansweredFactor($ticket),
            $this->historyFactor($ticket),
        ]));

        $score = array_sum(array_column($factors, 'weight'));

        return [
            'score' => round(min(1.0, $score), 3),
            'factors' => $factors,
        ];
    }

    /**
     * ¿Cuánto hay que acortar el plazo de escalado? De 1.0 (sin cambio) a 0.25.
     *
     * Acorta, nunca alarga: un ticket sin señales de riesgo escala exactamente
     * cuando escalaba antes. Así activar esto no puede retrasar nada.
     */
    public function urgencyMultiplier(Ticket $ticket): float
    {
        $score = $this->score($ticket)['score'];

        $threshold = (float) config('helpdesktickets.risk.min_score', 0.4);

        if ($score < $threshold) {
            return 1.0;
        }

        // A riesgo máximo, el plazo se reduce a la cuarta parte.
        return max(0.25, 1.0 - ($score * 0.75));
    }

    /**
     * Cliente enfadado. Es la señal más directa y la que más pesa.
     *
     * @return array{key: string, weight: float, detail: string}|null
     */
    private function sentimentFactor(Ticket $ticket): ?array
    {
        $avg = $ticket->customer_sentiment_avg;

        if ($avg === null || $avg >= -0.2) {
            return null;
        }

        // -0.2 no pesa nada; -1.0 pesa 0.35.
        $weight = min(0.35, abs((float) $avg - (-0.2)) * 0.44);

        return [
            'key' => 'sentimiento',
            'weight' => round($weight, 3),
            'detail' => 'El cliente muestra descontento en sus mensajes',
        ];
    }

    /**
     * Reaperturas: se dio por resuelto y no lo estaba. La señal más honesta de
     * que la respuesta anterior no sirvió.
     *
     * @return array{key: string, weight: float, detail: string}|null
     */
    private function reopenFactor(Ticket $ticket): ?array
    {
        $reopens = TicketHistory::query()
            ->where('ticket_id', $ticket->id)
            ->where('action_type', 'reopened')
            ->count();

        if ($reopens === 0) {
            return null;
        }

        return [
            'key' => 'reaperturas',
            'weight' => min(0.3, $reopens * 0.15),
            'detail' => $reopens === 1 ? 'Reabierto una vez' : "Reabierto {$reopens} veces",
        ];
    }

    /**
     * @return array{key: string, weight: float, detail: string}|null
     */
    private function slaFactor(Ticket $ticket): ?array
    {
        // Las columnas reales son sla_resolution_breached (booleana) y el
        // accessor is_overdue, que ya contempla el ticket cerrado.
        $breached = (bool) $ticket->sla_resolution_breached;

        if (! $breached && ! $ticket->is_overdue) {
            return null;
        }

        return [
            'key' => 'sla',
            'weight' => $breached ? 0.25 : 0.15,
            'detail' => $breached ? 'SLA de resolución incumplido' : 'Fuera de plazo de resolución',
        ];
    }

    /**
     * Mensajes del cliente seguidos sin respuesta del equipo. Alguien
     * escribiendo tres veces sin obtener contestación es un problema aunque
     * ninguna otra señal se haya disparado.
     *
     * @return array{key: string, weight: float, detail: string}|null
     */
    private function unansweredFactor(Ticket $ticket): ?array
    {
        $lastAgentReplyAt = $ticket->items()
            ->where('is_internal', false)
            ->whereNotNull('user_id')
            ->max('created_at');

        $pending = $ticket->items()
            ->where('is_internal', false)
            ->whereNull('user_id')
            ->when($lastAgentReplyAt, fn ($q) => $q->where('created_at', '>', $lastAgentReplyAt))
            ->count();

        if ($pending < 2) {
            return null;
        }

        return [
            'key' => 'sin_respuesta',
            'weight' => min(0.2, ($pending - 1) * 0.1),
            'detail' => "{$pending} mensajes del cliente sin respuesta",
        ];
    }

    /**
     * Cliente que ya venía puntuando mal. No es culpa de este ticket, pero es
     * exactamente el que no conviene que espere.
     *
     * @return array{key: string, weight: float, detail: string}|null
     */
    private function historyFactor(Ticket $ticket): ?array
    {
        if (! $ticket->customer_id) {
            return null;
        }

        $avgRating = Ticket::query()
            ->where('customer_id', $ticket->customer_id)
            ->whereKeyNot($ticket->id)
            ->whereNotNull('rating')
            ->avg('rating');

        if ($avgRating === null || $avgRating >= 3) {
            return null;
        }

        return [
            'key' => 'historial_csat',
            'weight' => 0.15,
            'detail' => 'Valoraciones previas bajas ('.round((float) $avgRating, 1).'/5)',
        ];
    }
}
