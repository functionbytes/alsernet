<?php

namespace Modules\Auth\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Auth\Models\EmailChangeToken;

class EmailChangeTokenPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('auth.audit.view');
    }

    public function view(User $user, EmailChangeToken $token): bool
    {
        return $user->can('auth.audit.view') || $token->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, EmailChangeToken $token): bool
    {
        return $token->user_id === $user->id;
    }

    public function delete(User $user, EmailChangeToken $token): bool
    {
        return $user->can('auth.audit.view') || $token->user_id === $user->id;
    }

    public function manage(User $user): bool
    {
        return $user->can('auth.audit.view');
    }
}
