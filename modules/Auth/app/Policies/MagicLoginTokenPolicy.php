<?php

namespace Modules\Auth\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Auth\Models\MagicLoginToken;

class MagicLoginTokenPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('auth.audit.view');
    }

    public function view(User $user, MagicLoginToken $token): bool
    {
        return $user->can('auth.audit.view') || $token->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, MagicLoginToken $token): bool
    {
        return false;
    }

    public function delete(User $user, MagicLoginToken $token): bool
    {
        return $user->can('auth.audit.view') || $token->user_id === $user->id;
    }

    public function manage(User $user): bool
    {
        return $user->can('auth.audit.view');
    }
}
