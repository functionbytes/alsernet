<?php

namespace Modules\HelpdeskChatFlow\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\HelpdeskChatFlow\Models\ChatFlow;

class ChatFlowPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('chatflow.view');
    }

    public function view(User $user, ChatFlow $chatFlow): bool
    {
        return $user->can('chatflow.view');
    }

    public function create(User $user): bool
    {
        return $user->can('chatflow.create');
    }

    public function update(User $user, ChatFlow $chatFlow): bool
    {
        return $user->can('chatflow.update');
    }

    public function delete(User $user, ChatFlow $chatFlow): bool
    {
        return $user->can('chatflow.delete');
    }

    public function manage(User $user): bool
    {
        return $user->can('chatflow.manage');
    }

    /**
     * Supervisor takes over a bot-handled conversation (stops the bot + reassigns).
     */
    public function takeOver(User $user): bool
    {
        return $user->can('chatflow.update');
    }
}
