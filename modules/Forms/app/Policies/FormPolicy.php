<?php

namespace Modules\Forms\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Forms\Models\Form;

class FormPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('Forms.forms.index');
    }

    public function view(User $user, Form $form): bool
    {
        return $user->can('Forms.forms.index');
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

    public function manage(User $user): bool
    {
        return $user->can('Forms.forms.manage');
    }

    public function settings(User $user): bool
    {
        return $user->can('Forms.settings.view');
    }
}
