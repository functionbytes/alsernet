<?php

namespace Modules\Template\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Template\Models\Shortcode;

class ShortcodePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('shortcode.view');
    }

    public function view(User $user, Shortcode $shortcode): bool
    {
        return $user->can('shortcode.view');
    }

    public function create(User $user): bool
    {
        return $user->can('shortcode.create');
    }

    public function update(User $user, Shortcode $shortcode): bool
    {
        return $user->can('shortcode.update');
    }

    public function delete(User $user, Shortcode $shortcode): bool
    {
        return $user->can('shortcode.delete');
    }

    public function manage(User $user): bool
    {
        return $user->can('shortcode.manage');
    }
}
