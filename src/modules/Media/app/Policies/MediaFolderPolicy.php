<?php

namespace Modules\Media\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Media\Models\MediaFolder;

class MediaFolderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('media.view');
    }

    public function view(User $user, MediaFolder $folder): bool
    {
        return $user->can('media.view')
            && $this->belongsToUser($user, $folder);
    }

    public function create(User $user): bool
    {
        return $user->can('media.create');
    }

    public function update(User $user, MediaFolder $folder): bool
    {
        return $user->can('media.update')
            && $this->belongsToUser($user, $folder);
    }

    public function delete(User $user, MediaFolder $folder): bool
    {
        return $user->can('media.delete')
            && $this->belongsToUser($user, $folder);
    }

    public function restore(User $user, MediaFolder $folder): bool
    {
        return $user->can('media.update')
            && $this->belongsToUser($user, $folder);
    }

    public function forceDelete(User $user, ?MediaFolder $folder = null): bool
    {
        if ($folder === null) {
            return $user->can('media.force-delete');
        }

        return $user->can('media.force-delete')
            && $this->belongsToUser($user, $folder);
    }

    protected function belongsToUser(User $user, MediaFolder $folder): bool
    {
        if ($user->can('media.manage')) {
            return true;
        }

        return $folder->user_id === $user->id;
    }
}
