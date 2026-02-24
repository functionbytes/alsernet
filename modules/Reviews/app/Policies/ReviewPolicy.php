<?php

namespace Modules\Reviews\Policies;

use App\Models\User;
use Modules\Reviews\Models\Review;

class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reviews.reviews.view');
    }

    public function view(User $user, Review $review): bool
    {
        // SECURITY FIX: Verify user has access to the review's location connection to prevent IDOR
        return $user->can('reviews.reviews.view')
            && ($review->location->connection->user_id === $user->id || $user->hasRole('super-admin'));
    }

    public function moderate(User $user, Review $review): bool
    {
        // SECURITY FIX: Verify user has access to the review's location connection to prevent IDOR
        return $user->can('reviews.moderate')
            && ($review->location->connection->user_id === $user->id || $user->hasRole('super-admin'));
    }

    public function export(User $user): bool
    {
        return $user->can('reviews.reviews.export');
    }
}
