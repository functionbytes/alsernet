<?php

namespace Modules\Campaign\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\CampaignTrackingLog;

/**
 * Disparado cuando un email de campaña se envía exitosamente.
 * Los listeners pueden actualizar métricas materializadas, métricas Prometheus, etc.
 */
class CampaignMessageSent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Campaign $campaign,
        public readonly CampaignTrackingLog $trackingLog,
    ) {}
}
