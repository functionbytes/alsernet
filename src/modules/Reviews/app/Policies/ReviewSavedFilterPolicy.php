<?php

namespace Modules\Reviews\Policies;

use App\Models\User;
use Modules\Reviews\Models\ReviewSavedFilter;

class ReviewSavedFilterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reviews.view');
    }

    public function view(User $user, ReviewSavedFilter $filter): bool
    {
        // Only the owner can view their saved filters
        return $user->can('reviews.view') && $filter->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('reviews.view');
    }

    public function update(User $user, ReviewSavedFilter $filter): bool
    {
        // Only the owner can update their saved filters
        return $user->can('reviews.view') && $filter->user_id === $user->id;
    }

    public function delete(User $user, ReviewSavedFilter $filter): bool
    {
        // Only the owner can delete their saved filters
        return $user->can('reviews.view') && $filter->user_id === $user->id;
    }
}
