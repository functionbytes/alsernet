<?php

namespace Modules\HelpdeskTickets\Services\TicketDetail;

use Illuminate\Support\Collection;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketRead;

/**
 * Pestaña Actividad del panel de detalle: marca de lectura del ticket y el
 * historial (spatie/activitylog) ya serializado. Extraído de
 * TicketDetailDataService (30-sep-2026) al trocear ese servicio por sección
 * de panel — ver el docblock de la clase façade para el porqué.
 */
class ActivityBuilder
{
    /**
     * Marca leído para este agente todo el hilo que acaba de abrir.
     * TicketRead::markAllReadFor() ya existía —el portal de agentes
     * (Agents/TicketsController) la llama desde 2025— pero el panel de
     * manager (esta vista, la única desde que se retiró "ficha
     * completa" el 8-sep-2026) nunca la invocaba: abrir un ticket aquí
     * no escribía ni una fila en helpdesk_ticket_reads, así que
     * getUnreadCountForUser() devolvía el total de mensajes del ticket
     * para siempre, sin importar cuántas veces se abriera (bug real,
     * confuso a simple vista en el listado — QA visual 14-sep-2026).
     *
     * "Ticket visto" en la pestaña Actividad, pedido a continuación del
     * fix anterior: solo cuando markAllReadFor() marcó algo REALMENTE
     * nuevo, no en cada apertura — un agente repasando el mismo ticket
     * varias veces (o la navegación J/K) no debe generar una entrada
     * por cada clic. activity()->log() es el camino manual v5 (a
     * diferencia de LogsActivity en Ticket, que usa v4 — ver el docblock
     * de la migración add_attribute_changes_to_activity_log_table):
     * ambos caminos escriben en la misma tabla, así que $ticket->
     * activities() (relación que ya alimenta esta pestaña más abajo) la
     * recoge igual que cualquier cambio automático.
     *
     * ?prefetch=1 (24-sep-2026): el panel precarga el detalle al pasar el
     * ratón por una fila; eso no es abrir el ticket, así que ni se marca
     * leído ni se registra "Ticket visto". La apertura real repite la
     * petición sin el parámetro.
     */
    public function recordView(Ticket $ticket): void
    {
        if (request()->boolean('prefetch')) {
            return;
        }

        if (TicketRead::markAllReadFor($ticket, auth()->id()) > 0) {
            activity()
                ->performedOn($ticket)
                ->causedBy(auth()->user())
                ->log('Ticket visto');
        }
    }

    /**
     * OJO: este proyecto tiene una copia vendored antigua de
     * spatie/laravel-activitylog dentro de modules/Activity/vendor/...
     * que el autoload PSR-4 resuelve ANTES que vendor/spatie/... (mismo
     * classmap-split ya documentado en el proyecto). Esa copia antigua
     * define activities() directamente (no activitiesAsSubject(), que
     * es de la copia nueva en vendor/ raíz y aquí nunca se carga) —
     * confirmado en runtime: activitiesAsSubject() lanzaba
     * BadMethodCallException real.
     *
     * @return array{activity: Collection<int, array<string, mixed>>, activity_total_count: int}
     */
    public function build(Ticket $ticket): array
    {
        // Total real ANTES de recortar con limit(20) — el JS lo usa para
        // avisar "mostrando los 20 más recientes" en vez de dejar el corte
        // silencioso (hallazgo LOW del audit: con 32+ entradas en un ticket
        // longevo, el badge del riel de iconos nunca podía delatar que
        // había más historial).
        $activityTotalCount = $ticket->activities()->count();

        $activity = $ticket->activities()
            ->with('causer')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'description' => $a->description,
                // ->name a secas devolvía SIEMPRE null: el User de esta app
                // guarda firstname/lastname y no tiene columna 'name', así
                // que la pestaña Actividad se pintaba entera sin autor.
                'causer' => $this->causerName($a->causer),
                'causer_kind' => $this->causerKind($a),
                'changes' => [
                    'old' => (array) data_get($a->properties, 'old', []),
                    'attributes' => (array) data_get($a->properties, 'attributes', []),
                ],
                'created_at' => $a->created_at?->toIso8601String(),
                'created_at_human' => $a->created_at?->diffForHumans(),
            ])->values();

        return [
            'activity' => $activity,
            // Ver comentario junto a $activityTotalCount: > count(activity)
            // cuando el limit(20) de arriba recortó historial real.
            'activity_total_count' => $activityTotalCount,
        ];
    }

    /**
     * Nombre visible del autor de una entrada de historial.
     */
    private function causerName(?object $causer): ?string
    {
        if (! $causer) {
            return null;
        }

        if (method_exists($causer, 'fullName') && ($full = trim((string) $causer->fullName())) !== '') {
            return $full;
        }

        $name = trim(($causer->firstname ?? '').' '.($causer->lastname ?? ''));

        return $name !== '' ? $name : ($causer->email ?? null);
    }

    /**
     * Quién provocó el cambio, para la etiqueta del mockup: un agente, el
     * propio cliente, una automatización o el sistema. Sin causer registrado
     * la entrada la escribió un proceso automático, no una persona.
     */
    private function causerKind(object $activity): string
    {
        if ($activity->causer) {
            return 'agent';
        }

        return str_contains(mb_strtolower((string) $activity->description), 'cliente')
            ? 'customer'
            : 'system';
    }
}
