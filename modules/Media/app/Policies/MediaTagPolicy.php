<?php

namespace Modules\Media\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Media\Models\MediaTag;

class MediaTagPolicy
{
    use HandlesAuthorization;

    public function update(User $user, MediaTag $tag): bool
    {
        if ($user->can('media.manage')) {
            return true;
        }

        return $tag->user_id === $user->id;
    }

    public function delete(User $user, MediaTag $tag): bool
    {
        if ($user->can('media.manage')) {
            return true;
        }

        return $tag->user_id === $user->id;
    }
}
