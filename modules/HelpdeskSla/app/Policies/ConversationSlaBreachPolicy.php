<?php

namespace Modules\HelpdeskSla\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\HelpdeskSla\Models\ConversationSlaBreach;

class ConversationSlaBreachPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('helpdesksla.view');
    }

    public function view(User $user, ConversationSlaBreach $breach): bool
    {
        return $user->can('helpdesksla.view');
    }

    public function update(User $user, ConversationSlaBreach $breach): bool
    {
        return $user->can('helpdesksla.manage');
    }

    public function delete(User $user, ConversationSlaBreach $breach): bool
    {
        return $user->can('helpdesksla.manage');
    }
}
