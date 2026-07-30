<?php

namespace Modules\HelpdeskIntegration\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\HelpdeskIntegration\Models\IntegrationProvider;

class IntegrationProviderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('helpdeskintegration.providers.view');
    }

    public function view(User $user, IntegrationProvider $provider): bool
    {
        return $user->can('helpdeskintegration.providers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('helpdeskintegration.providers.create');
    }

    public function update(User $user, IntegrationProvider $provider): bool
    {
        return $user->can('helpdeskintegration.providers.update');
    }

    /**
     * Los proveedores nativos (con driver) no se pueden borrar, solo desactivar.
     */
    public function delete(User $user, IntegrationProvider $provider): bool
    {
        return $user->can('helpdeskintegration.providers.delete') && ! $provider->isNative();
    }

    public function manage(User $user): bool
    {
        return $user->can('helpdeskintegration.providers.manage');
    }
}
