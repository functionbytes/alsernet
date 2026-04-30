<?php

namespace Modules\Chat\Policies;

use App\Models\User;
use Modules\Chat\Models\IntegrationsHook;

class IntegrationsHookPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, IntegrationsHook $integrationsHook): bool
    {
        return $user->account_id === $integrationsHook->account_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, IntegrationsHook $integrationsHook): bool
    {
        return $user->account_id === $integrationsHook->account_id;
    }

    public function delete(User $user, IntegrationsHook $integrationsHook): bool
    {
        return $user->account_id === $integrationsHook->account_id;
    }

    public function restore(User $user, IntegrationsHook $integrationsHook): bool
    {
        return $user->account_id === $integrationsHook->account_id;
    }

    public function forceDelete(User $user, IntegrationsHook $integrationsHook): bool
    {
        return $user->account_id === $integrationsHook->account_id;
    }
}
