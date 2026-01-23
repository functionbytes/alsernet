<?php

namespace Modules\HelpdeskChat\Policies;

use App\Models\User;
use Modules\HelpdeskChat\Models\Label;

class LabelPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Label $label): bool
    {
        return $user->account_id === $label->account_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Label $label): bool
    {
        return $user->account_id === $label->account_id;
    }

    public function delete(User $user, Label $label): bool
    {
        return $user->account_id === $label->account_id;
    }

    public function restore(User $user, Label $label): bool
    {
        return $user->account_id === $label->account_id;
    }

    public function forceDelete(User $user, Label $label): bool
    {
        return $user->account_id === $label->account_id;
    }
}
