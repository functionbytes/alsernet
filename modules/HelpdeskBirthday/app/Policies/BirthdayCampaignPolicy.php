<?php

namespace Modules\HelpdeskBirthday\Policies;

use App\Models\User;
use Modules\HelpdeskBirthday\Models\BirthdayCampaign;

/**
 * Autorización de las campañas de cumpleaños.
 *
 * Las rutas ya llevan `can:helpdeskbirthday.*`, así que esto no cambia quién
 * entra: existe para que la autorización sea consultable desde código
 * (`$user->can('update', $campaign)`, `@can` en vistas) y para poder añadir
 * reglas por estado sin tocar cada ruta — como que una campaña cerrada ya no
 * se pause. Mismo patrón que HelpdeskSla\Policies\ConversationSlaBreachPolicy.
 */
class BirthdayCampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('helpdeskbirthday.view');
    }

    public function view(User $user, BirthdayCampaign $campaign): bool
    {
        return $user->can('helpdeskbirthday.view');
    }

    public function manage(User $user): bool
    {
        return $user->can('helpdeskbirthday.manage');
    }

    /**
     * El permiso no basta: el estado también manda. Así la regla vive en un
     * sitio y no repartida entre el controlador y la vista.
     */
    public function pause(User $user, BirthdayCampaign $campaign): bool
    {
        return $user->can('helpdeskbirthday.manage') && $campaign->canBePaused();
    }

    public function resume(User $user, BirthdayCampaign $campaign): bool
    {
        return $user->can('helpdeskbirthday.manage') && $campaign->canBeResumed();
    }

    public function cancel(User $user, BirthdayCampaign $campaign): bool
    {
        return $user->can('helpdeskbirthday.manage') && $campaign->canBeCancelled();
    }

    public function retry(User $user, BirthdayCampaign $campaign): bool
    {
        return $user->can('helpdeskbirthday.manage') && $campaign->failed_count > 0;
    }

    public function delete(User $user, BirthdayCampaign $campaign): bool
    {
        return $user->can('helpdeskbirthday.manage');
    }
}
