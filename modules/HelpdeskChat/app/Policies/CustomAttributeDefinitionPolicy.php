<?php

namespace Modules\HelpdeskChat\Policies;

use App\Models\User;
use Modules\HelpdeskChat\Models\Contacts\ContactAttribute;

class CustomAttributeDefinitionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ContactAttribute $customAttributeDefinition): bool
    {
        return $user->account_id === $customAttributeDefinition->account_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ContactAttribute $customAttributeDefinition): bool
    {
        return $user->account_id === $customAttributeDefinition->account_id;
    }

    public function delete(User $user, ContactAttribute $customAttributeDefinition): bool
    {
        return $user->account_id === $customAttributeDefinition->account_id;
    }

    public function restore(User $user, ContactAttribute $customAttributeDefinition): bool
    {
        return $user->account_id === $customAttributeDefinition->account_id;
    }

    public function forceDelete(User $user, ContactAttribute $customAttributeDefinition): bool
    {
        return $user->account_id === $customAttributeDefinition->account_id;
    }
}
