<?php

namespace Modules\Template\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Template\Models\Template;

class TemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('template.view');
    }

    public function view(User $user, Template $template): bool
    {
        return $user->can('template.view');
    }

    public function create(User $user): bool
    {
        return $user->can('template.create');
    }

    public function update(User $user, Template $template): bool
    {
        return $user->can('template.edit');
    }

    public function delete(User $user, Template $template): bool
    {
        return $user->can('template.delete');
    }

    public function restore(User $user, Template $template): bool
    {
        return $user->can('template.restore');
    }

    public function forceDelete(User $user, Template $template): bool
    {
        return $user->can('template.force-delete');
    }
}
