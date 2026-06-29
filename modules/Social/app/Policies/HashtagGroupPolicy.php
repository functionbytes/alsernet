<?php

namespace Modules\Social\Policies;

use App\Models\User;
use Modules\Social\Models\HashtagGroup;

class HashtagGroupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->account_id !== null;
    }

    public function view(User $user, HashtagGroup $hashtagGroup): bool
    {
        return $user->account_id === $hashtagGroup->account_id;
    }

    public function create(User $user): bool
    {
        return $user->account_id !== null;
    }

    public function update(User $user, HashtagGroup $hashtagGroup): bool
    {
        return $user->account_id === $hashtagGroup->account_id;
    }

    public function delete(User $user, HashtagGroup $hashtagGroup): bool
    {
        return $user->account_id === $hashtagGroup->account_id;
    }
}
