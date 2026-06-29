<?php

namespace Modules\Ecommerce\Policies;

use App\Models\User;
use Modules\Ecommerce\Models\LegalPage;

class LegalPagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ecommerce.legal-page.view');
    }

    public function view(User $user, LegalPage $legalPage): bool
    {
        return $user->hasPermissionTo('ecommerce.legal-page.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('ecommerce.legal-page.manage');
    }

    public function update(User $user, LegalPage $legalPage): bool
    {
        return $user->hasPermissionTo('ecommerce.legal-page.manage');
    }

    public function delete(User $user, LegalPage $legalPage): bool
    {
        return $user->hasPermissionTo('ecommerce.legal-page.manage');
    }
}
