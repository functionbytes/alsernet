<?php

namespace Modules\Chat\Policies;

use App\Models\User;
use Modules\Chat\Models\Canneds\Canned;

class CannedResponsePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Canned $canned): bool
    {
        return $user->account_id === $canned->account_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Canned $canned): bool
    {
        return $user->account_id === $canned->account_id;
    }

    public function delete(User $user, Canned $canned): bool
    {
        return $user->account_id === $canned->account_id;
    }

    public function restore(User $user, Canned $canned): bool
    {
        return $user->account_id === $canned->account_id;
    }

    public function forceDelete(User $user, Canned $canned): bool
    {
        return $user->account_id === $canned->account_id;
    }
}
