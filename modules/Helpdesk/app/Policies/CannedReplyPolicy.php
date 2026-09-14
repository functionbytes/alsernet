<?php

namespace Modules\Helpdesk\Policies;

use App\Models\User;
use Modules\Helpdesk\Models\CannedReply;

class CannedReplyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.canned-replies.view');
    }

    public function view(User $user, CannedReply $cannedReply): bool
    {
        return $user->hasPermissionTo('helpdesk.canned-replies.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.canned-replies.create');
    }

    public function update(User $user, CannedReply $cannedReply): bool
    {
        return $user->hasPermissionTo('helpdesk.canned-replies.update');
    }

    public function delete(User $user, CannedReply $cannedReply): bool
    {
        return $user->hasPermissionTo('helpdesk.canned-replies.delete');
    }

    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.canned-replies.manage');
    }
}
