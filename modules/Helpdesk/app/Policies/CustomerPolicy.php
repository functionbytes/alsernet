<?php

namespace Modules\Helpdesk\Policies;

use App\Models\User;
use Modules\Helpdesk\Models\Customer;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.customers.view');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('helpdesk.customers.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.customers.create');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('helpdesk.customers.update');
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('helpdesk.customers.delete');
    }

    public function restore(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('helpdesk.customers.manage');
    }

    public function forceDelete(User $user, Customer $customer): bool
    {
        return $user->hasAnyRole(['super-admin']);
    }
}
