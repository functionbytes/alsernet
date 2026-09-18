<?php

namespace Modules\HelpdeskSocial\Policies;

use App\Models\User;
use Modules\HelpdeskSocial\Models\SocialComment;

class SocialCommentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesksocial.view');
    }

    public function view(User $user, SocialComment $comment): bool
    {
        return $user->hasPermissionTo('helpdesksocial.view');
    }

    public function reply(User $user, SocialComment $comment): bool
    {
        return $user->hasPermissionTo('helpdesksocial.view');
    }

    public function assign(User $user, SocialComment $comment): bool
    {
        return $user->hasPermissionTo('helpdesksocial.view');
    }

    public function markAsSpam(User $user, SocialComment $comment): bool
    {
        return $user->hasPermissionTo('helpdesksocial.view');
    }

    public function escalate(User $user, SocialComment $comment): bool
    {
        return $user->hasPermissionTo('helpdesksocial.view');
    }
}
