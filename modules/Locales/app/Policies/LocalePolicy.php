<?php

namespace Modules\Locales\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Locales\Models\Locale;

class LocalePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('locale.view');
    }

    public function view(User $user, Locale $locale): bool
    {
        return $user->can('locale.view');
    }

    public function create(User $user): bool
    {
        return $user->can('locale.create');
    }

    public function update(User $user, Locale $locale): bool
    {
        return $user->can('locale.update');
    }

    public function delete(User $user, Locale $locale): bool
    {
        return $user->can('locale.delete');
    }

    public function manage(User $user): bool
    {
        return $user->can('locale.view');
    }

    public function setDefault(User $user, Locale $locale): bool
    {
        return $user->can('locale.set-default');
    }

    public function toggle(User $user, Locale $locale): bool
    {
        return $user->can('locale.toggle');
    }

    public function translate(User $user): bool
    {
        return $user->can('locale.translate');
    }
}
