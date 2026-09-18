<?php

namespace Modules\HelpdeskTickets\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\HelpdeskTickets\Models\Macro;

class MacroPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('helpdesk.tickets.settings');
    }

    public function view(User $user, Macro $macro): bool
    {
        return $user->can('helpdesk.tickets.settings');
    }

    public function create(User $user): bool
    {
        return $user->can('helpdesk.tickets.settings');
    }

    public function update(User $user, Macro $macro): bool
    {
        return $user->can('helpdesk.tickets.settings');
    }

    public function delete(User $user, Macro $macro): bool
    {
        return $user->can('helpdesk.tickets.settings');
    }

    public function manage(User $user): bool
    {
        return $user->can('helpdesk.tickets.manage');
    }

    /**
     * Solo se puede ejecutar una macro activa que sea compartida o propia —
     * mismo criterio que list() usa para mostrarla. Evita que un agente
     * aplique por id una macro privada de otro usuario o desactivada.
     */
    public function apply(User $user, Macro $macro): bool
    {
        return $macro->is_active && ($macro->is_shared || $macro->user_id === $user->id);
    }
}
