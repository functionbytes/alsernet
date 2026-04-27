<?php

namespace Modules\Newsletter\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Newsletter\Models\Subscriber;

class SubscriberPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('Newsletter.subscribers.index');
    }

    public function view(User $user, Subscriber $subscriber): bool
    {
        return $user->can('Newsletter.subscribers.index');
    }

    public function create(User $user): bool
    {
        return $user->can('Newsletter.subscribers.manage');
    }

    public function update(User $user, Subscriber $subscriber): bool
    {
        return $user->can('Newsletter.subscribers.manage');
    }

    public function delete(User $user, Subscriber $subscriber): bool
    {
        return $user->can('Newsletter.subscribers.manage');
    }

    public function manage(User $user): bool
    {
        return $user->can('Newsletter.subscribers.manage');
    }
}
