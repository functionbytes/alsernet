<?php

namespace Modules\HelpdeskTickets\Services;

use Illuminate\Support\Facades\View;
use Modules\HelpdeskEmailActivity\Contracts\EmailLogEntityPanelRenderer;
use Modules\HelpdeskEmailActivity\Contracts\EmailLogEntitySummaryProvider;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Lado HelpdeskTickets del punto de extensión EntityPanelRegistry de
 * HelpdeskEmailActivity (ver el contrato EmailLogEntityPanelRenderer y el
 * docblock de EntityPanelRegistry): cuando el detalle de un email en
 * HelpdeskEmailActivity referencia un Ticket (entity_type === Ticket::class),
 * este renderer añade un mini-resumen de "tickets relacionados del mismo
 * cliente" al panel.
 *
 * NO reinventa la consulta: es el mismo dato que ya expone el tab
 * "Relacionados" del panel lateral de la bandeja de tickets — ver
 * TicketMailDetailDataController::relatedTickets(), que a su vez reusa
 * HelpdeskTicketBridgeService::getCustomerTickets() (ya usado hoy también
 * por Contactos 360). Aquí solo se reexpone en un sitio nuevo, con el mismo
 * límite de 10 y el mismo filtrado del ticket propio en PHP (no en SQL) —
 * ver el comentario de relatedTickets() para el porqué de esa limitación
 * (con ≥10 tickets del cliente, el resultado final puede traer menos de 10
 * tras excluir el ticket actual).
 *
 * Se registra con `new self()` (sin inyectar nada por constructor) desde
 * HelpdeskTicketsServiceProvider::boot(), porque
 * EntityPanelRegistry::register() recibe la instancia directamente en vez de
 * resolver un FQCN por el contenedor — así que las dependencias (el bridge
 * service, la vista) se resuelven dentro de render(), igual que ya hace
 * relatedTickets() con `app(HelpdeskTicketBridgeService::class)`.
 */
class TicketEmailLogPanelRenderer implements EmailLogEntityPanelRenderer, EmailLogEntitySummaryProvider
{
    public function supports(string $entityType): bool
    {
        return $entityType === Ticket::class;
    }

    public function render(EmailLog $emailLog): ?string
    {
        /** @var Ticket|null $ticket */
        $ticket = Ticket::find($emailLog->entity_id);

        if (! $ticket) {
            return null;
        }

        $relatedTickets = collect();

        // Sin cliente vinculado no hay "otros tickets del mismo cliente" que
        // buscar (mismo guard que relatedTickets() en el controller
        // original) — se renderiza igualmente la vista, que ya sabe mostrar
        // el mensaje de "sin relacionados" con una lista vacía.
        if ($ticket->customer) {
            $relatedTickets = app(HelpdeskTicketBridgeService::class)
                ->getCustomerTickets($ticket->customer, 10)
                ->reject(fn (Ticket $t) => $t->id === $ticket->id)
                ->values();
        }

        return View::make('helpdesktickets::partials.email-log-panel', [
            'ticket' => $ticket,
            'relatedTickets' => $relatedTickets,
        ])->render();
    }

    /**
     * Ficha del ticket para la tarjeta "Entidad relacionada" del detalle del
     * email (ver EmailLogEntitySummaryProvider).
     *
     * Cada fila se omite si el ticket no tiene ese dato: sin cliente
     * vinculado o sin agente asignado NO se pinta la etiqueta con un guion,
     * porque "sin asignar" y "asignado a nadie" no son lo mismo para quien
     * lee el panel — es mejor que la fila no esté.
     *
     * @return array{icon: string, title: string, badge: ?string, subtitle: ?string, rows: list<array{label: string, value: string}>}|null
     */
    public function summary(EmailLog $emailLog): ?array
    {
        /** @var Ticket|null $ticket */
        $ticket = Ticket::with(['status', 'customer', 'assignee'])->find($emailLog->entity_id);

        if (! $ticket) {
            return null;
        }

        $rows = [];

        if ($ticket->customer?->name) {
            $rows[] = [
                'label' => __('helpdesktickets::helpdesktickets.email_log_panel.customer'),
                'value' => $ticket->customer->name,
            ];
        }

        if ($ticket->assignee?->fullName()) {
            $rows[] = [
                'label' => __('helpdesktickets::helpdesktickets.email_log_panel.assignee'),
                'value' => $ticket->assignee->fullName(),
            ];
        }

        return [
            'icon' => 'fa-ticket',
            'title' => $ticket->ticket_number ?: ('#'.$ticket->id),
            'badge' => $ticket->status?->name,
            'subtitle' => $ticket->subject ?: null,
            'rows' => $rows,
        ];
    }
}
