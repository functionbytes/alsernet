<?php

namespace Modules\Helpdesk\Policies;

use App\Models\User;
use Modules\Helpdesk\Models\ConversationTag;

class ConversationTagPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.tags.view');
    }

    public function view(User $user, ConversationTag $conversationTag): bool
    {
        return $user->hasPermissionTo('helpdesk.tags.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.tags.create');
    }

    public function update(User $user, ConversationTag $conversationTag): bool
    {
        return $user->hasPermissionTo('helpdesk.tags.update');
    }

    public function delete(User $user, ConversationTag $conversationTag): bool
    {
        return $user->hasPermissionTo('helpdesk.tags.delete');
    }

    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.tags.manage');
    }
}
