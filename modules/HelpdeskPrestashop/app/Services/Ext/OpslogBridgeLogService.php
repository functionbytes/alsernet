<?php

namespace Modules\HelpdeskPrestashop\Services\Ext;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

/**
 * Registro del puente (pieza 37): lee el log de llamadas que alsernetbridge
 * ya escribe (alsernetbridge_api_log) a través de la acción opslog.bridge_log,
 * y lanza las dos operaciones de la pantalla.
 */
class OpslogBridgeLogService
{
    public const RESULTS = ['all', 'failures', 'timeout', 'error', 'rejected', 'cache'];

    public const WARM_LOCK = 'helpdeskprestashop:opslog:warm-cache';

    public function __construct(
        private readonly PrestashopContextService $prestashop
    ) {}

    /**
     * @throws PsUpstreamException si el puente no responde
     */
    public function read(int $hours, string $result): ?array
    {
        return $this->prestashop->callBridge('opslog.bridge_log', [
            'hours' => $hours,
            'result' => in_array($result, self::RESULTS, true) ? $result : 'all',
            'limit' => (int) config('helpdeskprestashop.ext.opslog.bridge_log.rows', 60),
            // Umbral de "timeout": lo que Laravel espera antes de cortar. Una
            // llamada más larga terminó en PS pero el agente vio un error.
            'timeout_ms' => (int) config('helpdeskprestashop.http_timeout', 25) * 1000,
        ]);
    }

    /**
     * @throws PsUpstreamException si el puente no responde
     */
    public function requeueDead(string $idempotencyKey): ?array
    {
        return $this->prestashop->callBridge('opslog.requeue_dead', [
            'limit' => (int) config('helpdeskprestashop.ext.opslog.bridge_log.requeue_batch', 50),
        ], $idempotencyKey);
    }

    /**
     * Encola helpdeskprestashop:warm-cache en la cola de calentado (la que ya
     * usa WarmPsCacheJob). Devuelve false si ya se lanzó hace poco.
     */
    public function queueWarmCache(): bool
    {
        $cooldown = (int) config('helpdeskprestashop.ext.opslog.bridge_log.warm_cooldown', 300);

        if (! Cache::add(self::WARM_LOCK, now()->toIso8601String(), $cooldown)) {
            return false;
        }

        try {
            Artisan::queue('helpdeskprestashop:warm-cache', [
                '--limit' => (int) config('helpdeskprestashop.ext.opslog.bridge_log.warm_limit', 200),
            ])->onQueue('helpdesk-ps-warming');
        } catch (\Throwable $e) {
            // Si la cola no aceptó el trabajo, no se bloquea el botón 5 minutos
            // por un calentado que nunca se lanzó.
            Cache::forget(self::WARM_LOCK);

            throw $e;
        }

        return true;
    }

    public function lastWarmAt(): ?string
    {
        $value = Cache::get(self::WARM_LOCK);

        return is_string($value) ? $value : null;
    }
}
