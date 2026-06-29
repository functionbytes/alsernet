<?php

namespace Modules\Social\Policies;

use App\Models\User;
use Modules\Social\Models\Template;

class TemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('social.view-templates');
    }

    public function view(User $user, Template $template): bool
    {
        return $user->account_id === $template->account_id && $user->can('social.view-templates');
    }

    public function create(User $user): bool
    {
        return $user->can('social.create-templates');
    }

    public function update(User $user, Template $template): bool
    {
        return $user->account_id === $template->account_id && $user->can('social.edit-templates');
    }

    public function delete(User $user, Template $template): bool
    {
        return $user->account_id === $template->account_id && $user->can('social.delete-templates');
    }
}
