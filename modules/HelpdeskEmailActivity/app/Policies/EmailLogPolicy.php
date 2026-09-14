<?php

namespace Modules\HelpdeskEmailActivity\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\HelpdeskEmailActivity\Models\EmailLog;

class EmailLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('helpdeskemailactivity.view');
    }

    public function view(User $user, EmailLog $emailLog): bool
    {
        return $user->can('helpdeskemailactivity.view');
    }

    public function resend(User $user, EmailLog $emailLog): bool
    {
        return $user->can('helpdeskemailactivity.manage');
    }

    public function delete(User $user, EmailLog $emailLog): bool
    {
        return $user->can('helpdeskemailactivity.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('helpdeskemailactivity.manage');
    }

    /**
     * Papelera de registros (30 días de recuperación, ver
     * EmailLogController::trash()/restore()/forceDestroy()) — mismo permiso
     * que delete()/deleteAny(): quien puede borrar es quien puede ver,
     * restaurar y purgar la papelera.
     */
    public function restore(User $user, EmailLog $emailLog): bool
    {
        return $user->can('helpdeskemailactivity.manage');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('helpdeskemailactivity.manage');
    }

    public function forceDelete(User $user, EmailLog $emailLog): bool
    {
        return $user->can('helpdeskemailactivity.manage');
    }

    public function export(User $user): bool
    {
        return $user->can('helpdeskemailactivity.view');
    }

    public function manage(User $user): bool
    {
        return $user->can('helpdeskemailactivity.manage');
    }
}
