<?php

namespace Modules\Auth\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Auth\Models\Session;

class SessionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('auth.sessions.view');
    }

    public function view(User $user, Session $session): bool
    {
        return $user->can('auth.sessions.view') || $session->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Session $session): bool
    {
        return $user->can('auth.sessions.view') || $session->user_id === $user->id;
    }

    public function delete(User $user, Session $session): bool
    {
        return $user->can('auth.sessions.view') || $session->user_id === $user->id;
    }

    public function manage(User $user): bool
    {
        return $user->can('auth.sessions.view');
    }
}
