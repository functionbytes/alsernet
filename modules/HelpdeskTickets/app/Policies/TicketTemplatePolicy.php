<?php

namespace Modules\HelpdeskTickets\Policies;

use App\Models\User;
use Modules\HelpdeskTickets\Models\TicketTemplate;

class TicketTemplatePolicy
{
    /**
     * Quien llega a la pagina (gateada por rol en el registro de rutas) puede
     * ver el listado — el propio listado separa generales de "mis plantillas".
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TicketTemplate $ticketTemplate): bool
    {
        return $ticketTemplate->isGeneral() || $ticketTemplate->created_by === $user->id;
    }

    /**
     * Cualquiera que llegue a la pagina puede crear al menos una plantilla
     * personal propia; el controller decide si además puede marcarla general
     * segun helpdesk.tickets.manage.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Una plantilla personal solo la edita su dueño. Una general requiere el
     * permiso de gestion completa (hoy solo super-admin/super-settings).
     */
    public function update(User $user, TicketTemplate $ticketTemplate): bool
    {
        if ($ticketTemplate->created_by === $user->id) {
            return true;
        }

        return $ticketTemplate->isGeneral() && $user->hasPermissionTo('helpdesk.tickets.manage');
    }

    public function delete(User $user, TicketTemplate $ticketTemplate): bool
    {
        return $this->update($user, $ticketTemplate);
    }
}
