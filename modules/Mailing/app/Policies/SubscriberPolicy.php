<?php

namespace Modules\Mailrelay\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Mailrelay\Entities\Subscriber;

class SubscriberPolicy
{
    use HandlesAuthorization, HasSafePermissionCheck;

    /**
     * Determine if the user can view any subscribers.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'mailrelay.subscribers.view');
    }

    /**
     * Determine if the user can view the subscriber.
     */
    public function view(User $user, Subscriber $subscriber): bool
    {
        return $this->hasPermission($user, 'mailrelay.subscribers.view');
    }

    /**
     * Determine if the user can create subscribers.
     */
    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'mailrelay.subscribers.create');
    }

    /**
     * Determine if the user can update the subscriber.
     */
    public function update(User $user, Subscriber $subscriber): bool
    {
        return $this->hasPermission($user, 'mailrelay.subscribers.edit');
    }

    /**
     * Determine if the user can delete the subscriber.
     */
    public function delete(User $user, Subscriber $subscriber): bool
    {
        return $this->hasPermission($user, 'mailrelay.subscribers.delete');
    }

    /**
     * Determine if the user can manage subscriber groups.
     */
    public function manageGroups(User $user, Subscriber $subscriber): bool
    {
        return $this->hasPermission($user, 'mailrelay.subscribers.manage');
    }

    /**
     * Determine if the user can sync subscribers with Mailrelay.
     */
    public function sync(User $user): bool
    {
        return $this->hasPermission($user, 'mailrelay.subscribers.sync');
    }
}
