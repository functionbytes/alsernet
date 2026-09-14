<?php

namespace Modules\Media\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Media\Models\MediaFile;

class MediaFilePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('media.view');
    }

    public function view(User $user, MediaFile $file): bool
    {
        return $user->can('media.view')
            && $this->belongsToUser($user, $file);
    }

    public function create(User $user): bool
    {
        return $user->can('media.create');
    }

    public function update(User $user, MediaFile $file): bool
    {
        return $user->can('media.update')
            && $this->belongsToUser($user, $file);
    }

    public function delete(User $user, MediaFile $file): bool
    {
        return $user->can('media.delete')
            && $this->belongsToUser($user, $file);
    }

    public function restore(User $user, MediaFile $file): bool
    {
        return $user->can('media.update')
            && $this->belongsToUser($user, $file);
    }

    public function forceDelete(User $user, ?MediaFile $file = null): bool
    {
        if ($file === null) {
            return $user->can('media.force-delete');
        }

        return $user->can('media.force-delete')
            && $this->belongsToUser($user, $file);
    }

    protected function belongsToUser(User $user, MediaFile $file): bool
    {
        if ($user->can('media.manage')) {
            return true;
        }

        return $file->user_id === $user->id;
    }
}
