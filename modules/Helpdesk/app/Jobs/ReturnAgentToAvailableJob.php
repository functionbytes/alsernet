<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\AgentSettings;
use Modules\Helpdesk\Services\AgentPresenceService;

/**
 * Returns an agent to "available" after the away-mode auto-return delay
 * (En 1 hora / En 4 horas / Mañana 09:00). Dispatched with a delay when the
 * agent goes away; a no-op if they already came back or re-scheduled — the
 * $scheduledFor stamp is compared against the persisted auto_return_at so only
 * the most recent request wins.
 */
class ReturnAgentToAvailableJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 10;

    public function __construct(
        private readonly int $userId,
        private readonly string $scheduledFor,
    ) {
        $this->onQueue('helpdesk');
    }

    public function handle(AgentPresenceService $presence): void
    {
        $settings = AgentSettings::query()->where('user_id', $this->userId)->first();

        if (! $settings) {
            return;
        }

        // El agente ya volvió a disponible manualmente.
        if ($settings->presence_state === AgentSettings::PRESENCE_AVAILABLE) {
            return;
        }

        // El agente re-programó su vuelta: otro job (más reciente) se encargará.
        $prefs = $settings->preferences ?? [];
        if (($prefs['auto_return_at'] ?? null) !== $this->scheduledFor) {
            return;
        }

        $presence->setState($this->userId, AgentSettings::PRESENCE_AVAILABLE);

        $settings->update([
            'preferences' => array_merge($prefs, ['auto_return' => 'manual', 'auto_return_at' => null]),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ReturnAgentToAvailableJob failed', [
            'user_id' => $this->userId,
            'error' => $e->getMessage(),
        ]);
    }
}
