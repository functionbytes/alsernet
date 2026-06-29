<?php

namespace Modules\Remarketing\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Remarketing\Models\Template;

class TemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('remarketing.templates.view');
    }

    public function view(User $user, Template $template): bool
    {
        return $user->can('remarketing.templates.view') && $this->owns($user, $template);
    }

    public function create(User $user): bool
    {
        return $user->can('remarketing.templates.create');
    }

    public function update(User $user, Template $template): bool
    {
        return $user->can('remarketing.templates.update') && $this->owns($user, $template);
    }

    public function delete(User $user, Template $template): bool
    {
        return $user->can('remarketing.templates.delete') && $this->owns($user, $template);
    }

    public function manage(User $user): bool
    {
        return $user->can('remarketing.manage');
    }

    protected function owns(User $user, Template $template): bool
    {
        if ($user->can('remarketing.manage')) {
            return true;
        }

        if ($template->is_global) {
            return true;
        }

        return $template->store?->user_id === $user->id;
    }
}
