<?php

namespace Modules\Notification\Policies;

use App\Models\User;

class NotificationSettingsPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('notification.settings.view');
    }

    public function update(User $user): bool
    {
        return $user->can('notification.settings.update');
    }
}
