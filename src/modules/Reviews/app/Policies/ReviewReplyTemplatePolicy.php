<?php

namespace Modules\Reviews\Policies;

use App\Models\User;
use Modules\Reviews\Models\ReviewReplyTemplate;

class ReviewReplyTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reviews.templates.view');
    }

    public function view(User $user, ReviewReplyTemplate $template): bool
    {
        // SECURITY FIX: Only creator or super-admin can view templates, prevents IDOR
        return $user->can('reviews.templates.view')
            && ($template->created_by === $user->id || $user->hasRole('super-admin'));
    }

    public function create(User $user): bool
    {
        return $user->can('reviews.templates.create');
    }

    public function update(User $user, ReviewReplyTemplate $template): bool
    {
        // SECURITY FIX: Only creator or super-admin can update templates, prevents IDOR
        return $user->can('reviews.templates.update')
            && ($template->created_by === $user->id || $user->hasRole('super-admin'));
    }

    public function delete(User $user, ReviewReplyTemplate $template): bool
    {
        // SECURITY FIX: Only creator or super-admin can delete templates, prevents IDOR
        return $user->can('reviews.templates.delete')
            && ($template->created_by === $user->id || $user->hasRole('super-admin'));
    }
}
