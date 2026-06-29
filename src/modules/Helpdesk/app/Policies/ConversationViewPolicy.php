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
        return $user->hasPermissionTo('helpdesk.views.update');
    }

    public function delete(User $user, ConversationView $conversationView): bool
    {
        return $user->hasPermissionTo('helpdesk.views.delete');
    }

    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.views.manage');
    }
}
