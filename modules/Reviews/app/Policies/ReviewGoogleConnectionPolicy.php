<?php

namespace Modules\Reviews\Policies;

use App\Models\User;
use Modules\Reviews\Models\ReviewGoogleConnection;

class ReviewGoogleConnectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reviews.connections.view');
    }

    public function view(User $user, ReviewGoogleConnection $connection): bool
    {
        // SECURITY FIX: Verify ownership to prevent IDOR (Insecure Direct Object Reference)
        return $user->can('reviews.connections.view')
            && ($connection->user_id === $user->id || $user->hasRole('super-admin'));
    }

    public function create(User $user): bool
    {
        return $user->can('reviews.connections.create');
    }

    public function update(User $user, ReviewGoogleConnection $connection): bool
    {
        // SECURITY FIX: Verify ownership to prevent IDOR
        return $user->can('reviews.connections.edit')
            && ($connection->user_id === $user->id || $user->hasRole('super-admin'));
    }

    public function delete(User $user, ReviewGoogleConnection $connection): bool
    {
        // SECURITY FIX: Verify ownership to prevent IDOR
        return $user->can('reviews.connections.delete')
            && ($connection->user_id === $user->id || $user->hasRole('super-admin'));
    }

    public function revoke(User $user, ReviewGoogleConnection $connection): bool
    {
        // SECURITY FIX: Verify ownership to prevent IDOR
        return $user->can('reviews.connections.revoke')
            && ($connection->user_id === $user->id || $user->hasRole('super-admin'));
    }

    public function manage(User $user, ReviewGoogleConnection $connection): bool
    {
        // SECURITY FIX: Verify ownership to prevent IDOR
        return $user->can('reviews.connections.manage')
            && ($connection->user_id === $user->id || $user->hasRole('super-admin'));
    }
}
