<?php

namespace Modules\Media\Policies;

use App\Models\User;
use Modules\Media\Entities\MediaFile;

class MediaFilePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MediaFile $file): bool
    {
        return $file->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, MediaFile $file): bool
    {
        return $file->user_id === $user->id;
    }

    public function delete(User $user, MediaFile $file): bool
    {
        return $file->user_id === $user->id;
    }

    public function restore(User $user, MediaFile $file): bool
    {
        return $file->user_id === $user->id;
    }

    public function forceDelete(User $user, MediaFile $file): bool
    {
        return $file->user_id === $user->id;
    }
}
