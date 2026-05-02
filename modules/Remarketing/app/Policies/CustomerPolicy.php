<?php

namespace Modules\Remarketing\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Remarketing\Models\Customer;

class CustomerPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('remarketing.customers.view');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->can('remarketing.customers.view') && $this->ownsCustomer($user, $customer);
    }

    public function create(User $user): bool
    {
        return $user->can('remarketing.customers.create');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->can('remarketing.customers.update') && $this->ownsCustomer($user, $customer);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->can('remarketing.customers.delete') && $this->ownsCustomer($user, $customer);
    }

    public function export(User $user): bool
    {
        return $user->can('remarketing.customers.export');
    }

    public function manage(User $user): bool
    {
        return $user->can('remarketing.manage');
    }

    protected function ownsCustomer(User $user, Customer $customer): bool
    {
        if ($user->can('remarketing.manage')) {
            return true;
        }

        return $customer->store?->user_id === $user->id;
    }
}
