<?php

namespace Modules\Social\Policies;

use App\Models\User;
use Modules\Social\Models\RssFeed;

class RssFeedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->account_id !== null;
    }

    public function view(User $user, RssFeed $rssFeed): bool
    {
        return $user->account_id === $rssFeed->account_id;
    }

    public function create(User $user): bool
    {
        return $user->account_id !== null;
    }

    public function update(User $user, RssFeed $rssFeed): bool
    {
        return $user->account_id === $rssFeed->account_id;
    }

    public function delete(User $user, RssFeed $rssFeed): bool
    {
        return $user->account_id === $rssFeed->account_id;
    }
}
