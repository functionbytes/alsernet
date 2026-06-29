<?php

namespace Modules\Helpdesk\Policies;

use App\Models\User;
use Modules\Helpdesk\Models\ConversationStatus;

class ConversationStatusPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.statuses.view');
    }

    public function view(User $user, ConversationStatus $conversationStatus): bool
    {
        return $user->hasPermissionTo('helpdesk.statuses.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.statuses.create');
    }

    public function update(User $user, ConversationStatus $conversationStatus): bool
    {
        return $user->hasPermissionTo('helpdesk.statuses.update');
    }

    public function delete(User $user, ConversationStatus $conversationStatus): bool
    {
        return $user->hasPermissionTo('helpdesk.statuses.delete');
    }

    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.statuses.manage');
    }
}
