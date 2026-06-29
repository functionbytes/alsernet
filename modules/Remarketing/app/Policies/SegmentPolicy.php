<?php

namespace Modules\Remarketing\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Remarketing\Models\Segment;

class SegmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('remarketing.segments.view');
    }

    public function view(User $user, Segment $segment): bool
    {
        return $user->can('remarketing.segments.view') && $this->owns($user, $segment);
    }

    public function create(User $user): bool
    {
        return $user->can('remarketing.segments.create');
    }

    public function update(User $user, Segment $segment): bool
    {
        return $user->can('remarketing.segments.update') && $this->owns($user, $segment);
    }

    public function delete(User $user, Segment $segment): bool
    {
        return $user->can('remarketing.segments.delete') && $this->owns($user, $segment);
    }

    public function manage(User $user): bool
    {
        return $user->can('remarketing.manage');
    }

    protected function owns(User $user, Segment $segment): bool
    {
        if ($user->can('remarketing.manage')) {
            return true;
        }

        return $segment->store?->user_id === $user->id;
    }
}
