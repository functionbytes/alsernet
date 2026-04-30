<?php

namespace Modules\Chat\Policies;

use App\Models\User;
use Modules\Chat\Models\Customers\CustomerAttribute;

class CustomAttributeDefinitionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CustomerAttribute $customAttributeDefinition): bool
    {
        return $user->account_id === $customAttributeDefinition->account_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CustomerAttribute $customAttributeDefinition): bool
    {
        return $user->account_id === $customAttributeDefinition->account_id;
    }

    public function delete(User $user, CustomerAttribute $customAttributeDefinition): bool
    {
        return $user->account_id === $customAttributeDefinition->account_id;
    }

    public function restore(User $user, CustomerAttribute $customAttributeDefinition): bool
    {
        return $user->account_id === $customAttributeDefinition->account_id;
    }

    public function forceDelete(User $user, CustomerAttribute $customAttributeDefinition): bool
    {
        return $user->account_id === $customAttributeDefinition->account_id;
    }
}
