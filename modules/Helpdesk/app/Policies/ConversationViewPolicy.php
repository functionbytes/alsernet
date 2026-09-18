<?php

namespace Modules\Helpdesk\Policies;

use App\Models\User;
use Modules\Helpdesk\Models\ConversationView;

class ConversationViewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.views.view');
    }

    public function view(User $user, ConversationView $conversationView): bool
    {
        return $user->hasPermissionTo('helpdesk.views.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.views.create');
    }

    public function update(User $user, ConversationView $conversationView): bool
    {
        return $this->owns($user, $conversationView)
            || $user->hasPermissionTo('helpdesk.views.update');
    }

    public function delete(User $user, ConversationView $conversationView): bool
    {
        // Las vistas guardadas son personales: su dueño siempre puede
        // eliminarlas; un gestor con el permiso puede administrar las ajenas.
        return $this->owns($user, $conversationView)
            || $user->hasPermissionTo('helpdesk.views.manage');
    }

    protected function owns(User $user, ConversationView $conversationView): bool
    {
        return (int) $conversationView->user_id === (int) $user->id;
    }

    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.views.manage');
    }
}
