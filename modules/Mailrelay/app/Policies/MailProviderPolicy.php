<?php

namespace Modules\Mailrelay\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Mailrelay\Entities\MailProvider;

class MailProviderPolicy
{
    use HandlesAuthorization, HasSafePermissionCheck;

    /**
     * Determine if the user can view any mail providers.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'mailrelay.providers.view');
    }

    /**
     * Determine if the user can view the mail provider.
     */
    public function view(User $user, MailProvider $provider): bool
    {
        return $this->hasPermission($user, 'mailrelay.providers.view');
    }

    /**
     * Determine if the user can create mail providers.
     */
    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'mailrelay.providers.create');
    }

    /**
     * Determine if the user can update the mail provider.
     */
    public function update(User $user, MailProvider $provider): bool
    {
        return $this->hasPermission($user, 'mailrelay.providers.edit');
    }

    /**
     * Determine if the user can delete the mail provider.
     */
    public function delete(User $user, MailProvider $provider): bool
    {
        // Cannot delete default provider
        if ($provider->is_default) {
            return false;
        }

        // Cannot delete if has campaigns
        if ($provider->campaigns()->exists()) {
            return false;
        }

        return $this->hasPermission($user, 'mailrelay.providers.delete');
    }

    /**
     * Determine if the user can test the mail provider connection.
     */
    public function test(User $user, MailProvider $provider): bool
    {
        return $this->hasPermission($user, 'mailrelay.providers.edit');
    }

    /**
     * Determine if the user can set provider as default.
     */
    public function setDefault(User $user, MailProvider $provider): bool
    {
        // Provider must be active to be set as default
        if (! $provider->is_active) {
            return false;
        }

        return $this->hasPermission($user, 'mailrelay.providers.edit');
    }

    /**
     * Determine if the user can toggle provider active status.
     */
    public function toggleActive(User $user, MailProvider $provider): bool
    {
        // Cannot deactivate default provider
        if ($provider->is_default && $provider->is_active) {
            return false;
        }

        return $this->hasPermission($user, 'mailrelay.providers.edit');
    }

    /**
     * Determine if the user can restore the mail provider.
     */
    public function restore(User $user, MailProvider $provider): bool
    {
        return $this->hasPermission($user, 'mailrelay.providers.delete');
    }

    /**
     * Determine if the user can permanently delete the mail provider.
     */
    public function forceDelete(User $user, MailProvider $provider): bool
    {
        return $this->hasPermission($user, 'mailrelay.providers.delete');
    }
}
