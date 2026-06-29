<?php

namespace Modules\Social\Policies;

use App\Models\User;
use Modules\Social\Models\SocialAccount;

class SocialAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->account_id !== null;
    }

    public function view(User $user, SocialAccount $socialAccount): bool
    {
        return $user->account_id === $socialAccount->account_id;
    }

    public function create(User $user): bool
    {
        return $user->account_id !== null;
    }

    public function update(User $user, SocialAccount $socialAccount): bool
    {
        return $user->account_id === $socialAccount->account_id;
    }

    public function delete(User $user, SocialAccount $socialAccount): bool
    {
        return $user->account_id === $socialAccount->account_id;
    }
}
