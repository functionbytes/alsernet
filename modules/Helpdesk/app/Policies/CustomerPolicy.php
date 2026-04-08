<?php

namespace Modules\Helpdesk\Policies;

use App\Models\User;
use Modules\Helpdesk\Models\Customer;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_customers');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('view_customers');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_customers');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('manage_customers');
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('manage_customers');
    }

    public function restore(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('manage_customers');
    }

    public function forceDelete(User $user, Customer $customer): bool
    {
        return $user->hasAnyRole(['super-admin']);
    }
}
