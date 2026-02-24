<?php

namespace Modules\Reviews\Policies;

use App\Models\User;
use Modules\Reviews\Models\ReviewAutoSuggestion;

class ReviewAutoSuggestionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reviews.templates.view');
    }

    public function view(User $user, ReviewAutoSuggestion $reviewAutoSuggestion): bool
    {
        return $user->can('reviews.templates.view');
    }

    public function create(User $user): bool
    {
        return $user->can('reviews.templates.create');
    }

    public function update(User $user, ReviewAutoSuggestion $reviewAutoSuggestion): bool
    {
        return $user->can('reviews.templates.update');
    }

    public function delete(User $user, ReviewAutoSuggestion $reviewAutoSuggestion): bool
    {
        return $user->can('reviews.templates.delete');
    }
}
