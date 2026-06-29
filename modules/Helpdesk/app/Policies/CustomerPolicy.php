<?php

namespace Modules\Helpdesk\Policies;

use App\Models\User;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.customers.view');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('helpdesk.customers.view')
            && $this->sharesInboxWith($user, $customer);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.customers.create');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('helpdesk.customers.update')
            && $this->sharesInboxWith($user, $customer);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('helpdesk.customers.delete')
            && $this->sharesInboxWith($user, $customer);
    }

    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.customers.manage');
    }

    public function restore(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('helpdesk.customers.manage');
    }

    public function forceDelete(User $user, Customer $customer): bool
    {
        return $user->hasAnyRole(['super-admin']);
    }

    /**
     * Aislamiento por inbox: un agente solo accede a un cliente con el que
     * comparte al menos una conversación en uno de sus inboxes asignados. Los
     * gestores (helpdesk.manage o helpdesk.customers.manage) acceden a todos.
     */
    protected function sharesInboxWith(User $user, Customer $customer): bool
    {
        if ($user->hasPermissionTo('helpdesk.manage') || $user->hasPermissionTo('helpdesk.customers.manage')) {
            return true;
        }

        $inboxIds = AgentInboxCapacity::query()
            ->where('user_id', $user->id)
            ->pluck('inbox_id');

        if ($inboxIds->isEmpty()) {
            return false;
        }

        return Conversation::query()
            ->where('customer_id', $customer->id)
            ->whereIn('inbox_id', $inboxIds)
            ->exists();
    }
}
