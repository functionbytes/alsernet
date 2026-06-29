<?php

namespace Modules\Social\Policies;

use App\Models\User;
use Modules\Social\Models\Label;

class LabelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->account_id !== null;
    }

    public function view(User $user, Label $label): bool
    {
        return $user->account_id === $label->account_id;
    }

    public function create(User $user): bool
    {
        return $user->account_id !== null;
    }

    public function update(User $user, Label $label): bool
    {
        return $user->account_id === $label->account_id;
    }

    public function delete(User $user, Label $label): bool
    {
        return $user->account_id === $label->account_id;
    }
}
