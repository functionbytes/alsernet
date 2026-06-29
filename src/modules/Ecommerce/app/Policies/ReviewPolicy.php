<?php

namespace Modules\Ecommerce\Policies;

use App\Models\User;
use Modules\Ecommerce\Models\Review;

class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ecommerce.reviews.index');
    }

    public function view(User $user, Review $review): bool
    {
        return $user->can('ecommerce.reviews.show');
    }

    public function create(User $user): bool
    {
        return $user->can('ecommerce.reviews.store');
    }

    public function update(User $user, Review $review): bool
    {
        return $user->can('ecommerce.reviews.update');
    }

    public function delete(User $user, Review $review): bool
    {
        return $user->can('ecommerce.reviews.destroy');
    }
}
