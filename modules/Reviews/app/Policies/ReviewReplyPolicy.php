<?php

namespace Modules\Reviews\Policies;

use App\Models\User;
use Modules\Reviews\Models\ReviewReply;

class ReviewReplyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reviews.replies.view');
    }

    public function view(User $user, ReviewReply $reply): bool
    {
        // SECURITY FIX: Verify user has access to the reply's review location connection to prevent IDOR
        return $user->can('reviews.replies.view')
            && ($reply->review->location->connection->user_id === $user->id || $user->hasRole('super-admin'));
    }

    public function create(User $user): bool
    {
        return $user->can('reviews.replies.create');
    }

    public function update(User $user, ReviewReply $reply): bool
    {
        // SECURITY FIX: Only creator or super-admin can edit, prevents IDOR
        return $user->can('reviews.replies.edit')
            && $reply->isDraft()
            && ($reply->created_by === $user->id || $user->hasRole('super-admin'));
    }

    public function delete(User $user, ReviewReply $reply): bool
    {
        // SECURITY FIX: Only creator or super-admin can delete, prevents IDOR
        return $user->can('reviews.replies.delete')
            && ($reply->created_by === $user->id || $user->hasRole('super-admin'));
    }

    public function approve(User $user, ReviewReply $reply): bool
    {
        // SECURITY FIX: Verify user has access to the reply's review location connection
        return $user->can('reviews.replies.approve')
            && ($reply->isDraft() || $reply->isPendingApproval())
            && ($reply->review->location->connection->user_id === $user->id || $user->hasRole('super-admin'));
    }

    public function publish(User $user, ReviewReply $reply): bool
    {
        // SECURITY FIX: Verify user has access to the reply's review location connection
        return $user->can('reviews.replies.publish')
            && ($reply->isDraft() || $reply->isApproved())
            && ($reply->review->location->connection->user_id === $user->id || $user->hasRole('super-admin'));
    }
}
