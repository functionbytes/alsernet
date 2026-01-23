<?php

namespace Modules\Media\Policies;

use App\Models\User;
use Modules\Media\Entities\MediaFolder;

class MediaFolderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MediaFolder $folder): bool
    {
        return $folder->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, MediaFolder $folder): bool
    {
        return $folder->user_id === $user->id;
    }

    public function delete(User $user, MediaFolder $folder): bool
    {
        return $folder->user_id === $user->id;
    }

    public function restore(User $user, MediaFolder $folder): bool
    {
        return $folder->user_id === $user->id;
    }

    public function forceDelete(User $user, MediaFolder $folder): bool
    {
        return $folder->user_id === $user->id;
    }
}
