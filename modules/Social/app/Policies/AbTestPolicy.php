<?php

namespace Modules\Social\Policies;

use App\Models\User;
use Modules\Social\Models\AbTest;

class AbTestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('social.view-analytics');
    }

    public function view(User $user, AbTest $abTest): bool
    {
        return $user->account_id === $abTest->account_id && $user->can('social.view-analytics');
    }

    public function create(User $user): bool
    {
        return $user->can('social.create-ab-tests');
    }

    public function update(User $user, AbTest $abTest): bool
    {
        return $user->account_id === $abTest->account_id && $user->can('social.create-ab-tests');
    }

    public function delete(User $user, AbTest $abTest): bool
    {
        return $user->account_id === $abTest->account_id && $user->can('social.create-ab-tests');
    }
}
