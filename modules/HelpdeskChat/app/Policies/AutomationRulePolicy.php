<?php

namespace Modules\HelpdeskChat\Policies;

use App\Models\User;
use Modules\HelpdeskChat\Models\Automations\AutomationRule;

class AutomationRulePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AutomationRule $automationRule): bool
    {
        return $user->account_id === $automationRule->account_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, AutomationRule $automationRule): bool
    {
        return $user->account_id === $automationRule->account_id;
    }

    public function delete(User $user, AutomationRule $automationRule): bool
    {
        return $user->account_id === $automationRule->account_id;
    }

    public function restore(User $user, AutomationRule $automationRule): bool
    {
        return $user->account_id === $automationRule->account_id;
    }

    public function forceDelete(User $user, AutomationRule $automationRule): bool
    {
        return $user->account_id === $automationRule->account_id;
    }
}
