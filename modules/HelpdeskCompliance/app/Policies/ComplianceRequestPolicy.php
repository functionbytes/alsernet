<?php

namespace Modules\HelpdeskCompliance\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\HelpdeskCompliance\Models\ComplianceRequest;

class ComplianceRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('helpdeskcompliance.view');
    }

    public function view(User $user, ComplianceRequest $request): bool
    {
        return $user->can('helpdeskcompliance.view');
    }
}
