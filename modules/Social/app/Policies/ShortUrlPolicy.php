<?php

namespace Modules\Social\Policies;

use App\Models\User;
use Modules\Social\Models\ShortUrl;

class ShortUrlPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->account_id !== null;
    }

    public function view(User $user, ShortUrl $shortUrl): bool
    {
        return $user->account_id === $shortUrl->account_id;
    }

    public function create(User $user): bool
    {
        return $user->account_id !== null;
    }

    public function update(User $user, ShortUrl $shortUrl): bool
    {
        return $user->account_id === $shortUrl->account_id;
    }

    public function delete(User $user, ShortUrl $shortUrl): bool
    {
        return $user->account_id === $shortUrl->account_id;
    }
}
