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
}
