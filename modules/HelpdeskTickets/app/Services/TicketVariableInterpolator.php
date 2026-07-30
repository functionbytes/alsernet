<?php

namespace Modules\HelpdeskTickets\Services;

use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Reemplaza variables {{...}} en texto (respuestas predefinidas, macros,
 * plantillas) con los datos del ticket. Fuente ÚNICA de las variables
 * soportadas — la usan MacroExecutor y los canned replies del agente.
 */
class TicketVariableInterpolator
{
    /**
     * @return array<string, string> mapa {{variable}} => valor
     */
    public function variables(Ticket $ticket): array
    {
        $ticket->loadMissing(['customer', 'assignee', 'status']);

        $subject = $ticket->subject ?? $ticket->title ?? '';
        $assignee = $ticket->assignee;
        $assigneeName = $assignee
            ? trim(($assignee->firstname ?? '').' '.($assignee->lastname ?? '')) ?: ($assignee->name ?? '')
            : 'Sin asignar';

        return [
            '{{ticket_number}}' => (string) $ticket->ticket_number,
            '{{ticket_subject}}' => $subject,
            '{{ticket_title}}' => $subject, // alias retrocompatible con las macros
            '{{ticket_status}}' => $ticket->status?->name ?? '',
            '{{ticket_priority}}' => (string) ($ticket->priority ?? ''),
            '{{customer_name}}' => $ticket->customer?->name ?? 'Cliente',
            '{{customer_email}}' => $ticket->customer?->email ?? '',
            '{{agent_name}}' => auth()->user()?->name ?? 'Agente',
            '{{assignee_name}}' => $assigneeName,
        ];
    }

    /**
     * Interpola las variables en el texto. Si $ticket es null o el texto vacío,
     * devuelve el texto tal cual (sin romper).
     */
    public function interpolate(?string $text, ?Ticket $ticket): string
    {
        if ($text === null || $text === '' || $ticket === null) {
            return (string) $text;
        }

        return strtr($text, $this->variables($ticket));
    }
}
