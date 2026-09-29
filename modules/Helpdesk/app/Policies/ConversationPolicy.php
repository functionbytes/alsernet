<?php

namespace Modules\Helpdesk\Policies;

use App\Models\User;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Conversation;

class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.conversations.view');
    }

    public function view(User $user, Conversation $conversation): bool
    {
        if ($this->isRestrictedToOwn($user)) {
            return $conversation->assignee_id === $user->id && $this->canAccessInbox($user, $conversation);
        }

        if (! $user->hasPermissionTo('helpdesk.conversations.view') && $conversation->assignee_id !== $user->id) {
            return false;
        }

        // 22-sep-2026: el permiso view-assigned-only (rol helpdesk-agent-
        // restricted) no se consultaba en ningún lado — un agente
        // restringido solo quedaba fuera del LISTADO (ver
        // buildFilteredConversationsQuery) pero podía seguir abriendo
        // cualquier conversación ajena de su inbox por URL directa
        // (/conversations/{id}) o por la API. La restricción tiene que
        // valer también acá, no solo en el listado.
        //
        // hasFullConversationAccess(): un rol admin/supervisor puede tener
        // view-assigned-only colado por un wildcard LIKE 'helpdesk.%' (así
        // pasó con helpdesk-admin — HelpdeskRolesSeeder::createAdminRole()
        // — y con super-admin/super-settings, que también arrastran
        // view-all + manage a la vez). Si el usuario YA tiene acceso amplio
        // (view-all o manage), esa restricción no debe aplicar aunque el
        // permiso esté mal asignado en el rol — si no, "supervisor con
        // acceso a todo" quedaba viendo 0 conversaciones.
        if (
            $user->hasPermissionTo('helpdesk.conversations.view-assigned-only')
            && ! $this->hasFullConversationAccess($user)
            && $conversation->assignee_id !== $user->id
        ) {
            return false;
        }

        return $this->canAccessInbox($user, $conversation);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.conversations.create');
    }

    public function update(User $user, Conversation $conversation): bool
    {
        // El rol 'helpdesk-agent-restricted' tiene 'helpdesk.conversations.update'
        // (necesita poder responder/adjuntar en SUS conversaciones) — sin este
        // corte explícito, ese permiso por sí solo habría bypaseado el chequeo
        // de assignee de abajo y le habría permitido editar cualquier
        // conversación de su bandeja, no solo las suyas.
        if ($this->isRestrictedToOwn($user)) {
            return $conversation->assignee_id === $user->id && $this->canAccessInbox($user, $conversation);
        }

        if (! $user->hasPermissionTo('helpdesk.conversations.update') && $conversation->assignee_id !== $user->id) {
            return false;
        }

        // Mismo criterio que view() — ver comentario ahí.
        if (
            $user->hasPermissionTo('helpdesk.conversations.view-assigned-only')
            && ! $this->hasFullConversationAccess($user)
            && $conversation->assignee_id !== $user->id
        ) {
            return false;
        }

        return $this->canAccessInbox($user, $conversation);
    }

    public function delete(User $user, Conversation $conversation): bool
    {
        return $user->hasPermissionTo('helpdesk.conversations.delete')
            && $this->canAccessInbox($user, $conversation);
    }

    public function restore(User $user, Conversation $conversation): bool
    {
        return $user->hasPermissionTo('helpdesk.conversations.manage')
            && $this->canAccessInbox($user, $conversation);
    }

    public function forceDelete(User $user, Conversation $conversation): bool
    {
        return $user->hasAnyRole(['super-admin']);
    }

    /**
     * Managers (and supervisors, via view-all) see all inboxes; agents are
     * restricted to their assigned inboxes.
     */
    protected function canAccessInbox(User $user, Conversation $conversation): bool
    {
        // Mismo criterio que ConversationsController::getUserInboxIds() —
        // ver comentario ahí: view-all también da acceso a cualquier inbox,
        // no solo helpdesk.manage.
        if ($user->hasPermissionTo('helpdesk.manage') || $user->hasPermissionTo('helpdesk.conversations.view-all')) {
            return true;
        }

        return AgentInboxCapacity::where('user_id', $user->id)
            ->where('inbox_id', $conversation->inbox_id)
            ->exists();
    }

    /**
     * 'helpdesk-agent-restricted': solo ve/actúa sobre conversaciones que le
     * asignaron a él mismo, nunca las de otro agente de su misma bandeja.
     * view-all y helpdesk.manage siempre ganan (un supervisor/admin con
     * view-assigned-only heredado de otro rol no debe quedar restringido).
     */
    protected function isRestrictedToOwn(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.conversations.view-assigned-only')
            && ! $user->hasPermissionTo('helpdesk.conversations.view-all')
            && ! $user->hasPermissionTo('helpdesk.manage');
    }

    /**
     * Si el usuario tiene alguno de estos, "view-assigned-only" no debe
     * restringirlo a lo suyo aunque el permiso esté presente en su rol —
     * ver comentario en view()/update().
     */
    protected function hasFullConversationAccess(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.manage')
            || $user->hasPermissionTo('helpdesk.conversations.view-all')
            || $user->hasPermissionTo('helpdesk.conversations.manage');
    }
}
