<?php

namespace Modules\HelpdeskTickets\Policies;

use App\Models\User;
use Modules\HelpdeskTickets\Models\TicketMail;

class TicketMailPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.emails.view')
            || $user->hasPermissionTo('helpdesk.tickets.manage');
    }

    public function view(User $user, TicketMail $mail): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.emails.view')
            || $user->hasPermissionTo('helpdesk.tickets.manage')
            || $mail->ticket?->assignee_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.emails.send')
            || $user->hasPermissionTo('helpdesk.tickets.update')
            || $user->hasPermissionTo('helpdesk.tickets.manage');
    }

    public function update(User $user, TicketMail $mail): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.emails.send')
            || $user->hasPermissionTo('helpdesk.tickets.update')
            || $user->hasPermissionTo('helpdesk.tickets.manage')
            || $mail->ticket?->assignee_id === $user->id;
    }

    public function resend(User $user, TicketMail $mail): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.emails.resend')
            || $user->hasPermissionTo('helpdesk.tickets.manage')
            || $mail->ticket?->assignee_id === $user->id;
    }

    /**
     * Enviar/reenviar a un destinatario distinto del cliente del ticket —
     * por defecto 'to' siempre se fija al email del cliente (ver
     * TicketMailsController::resolveOutboundRecipient()); esto solo se
     * consulta cuando se pide explícitamente otro destinatario.
     */
    public function sendToAnyRecipient(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.emails.send_to_any')
            || $user->hasPermissionTo('helpdesk.tickets.manage');
    }

    public function delete(User $user, TicketMail $mail): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.emails.delete')
            || $user->hasPermissionTo('helpdesk.tickets.manage');
    }
}
