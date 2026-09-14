<?php

namespace Modules\HelpdeskSocial\Observers;

use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskSocial\Models\SocialSlaPolicy;
use Modules\HelpdeskSocial\Services\SlaTrackingService;

/**
 * Invalida el caché de políticas SLA activas (SlaTrackingService) cada vez que
 * un administrador crea, edita, activa/desactiva o elimina una política.
 */
class SocialSlaPolicyObserver
{
    public function saved(SocialSlaPolicy $policy): void
    {
        Cache::forget(SlaTrackingService::POLICIES_CACHE_KEY);
    }

    public function deleted(SocialSlaPolicy $policy): void
    {
        Cache::forget(SlaTrackingService::POLICIES_CACHE_KEY);
    }
}
