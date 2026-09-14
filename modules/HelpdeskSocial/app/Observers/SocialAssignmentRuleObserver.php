<?php

namespace Modules\HelpdeskSocial\Observers;

use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskSocial\Models\SocialAssignmentRule;
use Modules\HelpdeskSocial\Services\SmartAssignmentService;

/**
 * Invalida el caché de reglas de asignación activas (SmartAssignmentService)
 * cada vez que un administrador crea, edita, activa/desactiva o elimina una regla.
 */
class SocialAssignmentRuleObserver
{
    public function saved(SocialAssignmentRule $rule): void
    {
        Cache::forget(SmartAssignmentService::RULES_CACHE_KEY);
    }

    public function deleted(SocialAssignmentRule $rule): void
    {
        Cache::forget(SmartAssignmentService::RULES_CACHE_KEY);
    }
}
