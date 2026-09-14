<?php

namespace Modules\Notification\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Notifications\DatabaseNotification;

class NotificationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('notification.view');
    }

    public function view(User $user, DatabaseNotification $notification): bool
    {
        return $user->can('notification.view')
            && $notification->notifiable_id === $user->id
            && $notification->notifiable_type === User::class;
    }
}
