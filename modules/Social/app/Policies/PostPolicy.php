<?php

namespace Modules\Social\Policies;

use App\Models\User;
use Modules\Social\Models\Post;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('social.view-posts');
    }

    public function view(User $user, Post $post): bool
    {
        return $user->account_id === $post->account_id && $user->can('social.view-posts');
    }

    public function create(User $user): bool
    {
        return $user->can('social.create-posts');
    }

    public function update(User $user, Post $post): bool
    {
        return $user->account_id === $post->account_id && $user->can('social.edit-posts');
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->account_id === $post->account_id && $user->can('social.delete-posts');
    }
}
