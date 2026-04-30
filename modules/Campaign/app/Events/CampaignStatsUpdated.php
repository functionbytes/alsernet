<?php

namespace Modules\Campaign\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Campaign\Models\Campaign;

/**
 * Evento broadcast vía Laravel Reverb para actualizar el dashboard
 * de métricas de campaña en tiempo real.
 */
class CampaignStatsUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Campaign $campaign,
        public readonly array $stats,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('campaign.'.$this->campaign->uid),
        ];
    }

    public function broadcastAs(): string
    {
        return 'stats.updated';
    }
}
