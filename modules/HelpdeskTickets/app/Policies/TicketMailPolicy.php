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

    public function resend(User $user, TicketMail $mail): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.emails.resend')
            || $user->hasPermissionTo('helpdesk.tickets.manage')
            || $mail->ticket?->assignee_id === $user->id;
    }

    public function delete(User $user, TicketMail $mail): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.emails.delete')
            || $user->hasPermissionTo('helpdesk.tickets.manage');
    }
}
