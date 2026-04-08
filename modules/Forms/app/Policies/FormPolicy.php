<?php

namespace Modules\Forms\Policies;

use App\Models\User;
use Modules\Forms\Models\Form;

class FormPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('Forms.submissions.index');
    }

    public function view(User $user, Form $form): bool
    {
        return $user->can('Forms.submissions.index');
    }

    public function create(User $user): bool
    {
        return $user->can('Forms.forms.create');
    }

    public function update(User $user, Form $form): bool
    {
        return $user->can('Forms.forms.edit');
    }

    public function delete(User $user, Form $form): bool
    {
        return $user->can('Forms.forms.delete');
    }
}
