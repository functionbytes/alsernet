<?php

namespace Modules\HelpdeskBirthday\Services;

use Illuminate\Support\Facades\Redis;
use Modules\HelpdeskBirthday\Models\BirthdayCampaign;
use Modules\HelpdeskBirthday\Models\BirthdayRecipient;
use Throwable;

/**
 * Salud del envío: detecta que los correos se están encolando pero no sale
 * ninguno.
 *
 * En este repo ya ha pasado con otros módulos — un worker caído seis horas sin
 * que nadie se enterara, y colas declaradas que nunca tuvieron worker. Aquí el
 * síntoma sería peor de lo normal: la campaña se ve "enviando", los contadores
 * no avanzan y los cumpleaños del día pasan de largo.
 *
 * Dos señales, ninguna requiere Horizon:
 *   - Cola llena: hay trabajos esperando en Redis.
 *   - Nada se mueve: hay destinatarios reservados ('sending') desde hace rato
 *     que nadie ha procesado. Esta es la fiable, porque no depende de poder
 *     hablar con Redis.
 */
class BirthdayQueueHealthService
{
    /** Un 'sending' que lleva más de esto sin resolverse es un atasco. */
    private const STUCK_MINUTES = 15;

    /**
     * @return array<string, mixed>
     */
    public function check(): array
    {
        $queue = (string) config('helpdeskbirthday.queue', 'birthdays');

        $stuck = BirthdayRecipient::query()
            ->where('status', BirthdayRecipient::STATUS_SENDING)
            ->where('updated_at', '<', now()->subMinutes(self::STUCK_MINUTES))
            ->count();

        $overdue = BirthdayRecipient::query()
            ->whereHas('campaign', fn ($q) => $q->whereIn('status', BirthdayCampaign::ACTIVE_STATUSES))
            ->where('status', BirthdayRecipient::STATUS_PENDING)
            ->where('scheduled_at', '<', now()->subMinutes(self::STUCK_MINUTES))
            ->count();

        return [
            'queue' => $queue,
            'pending_in_redis' => $this->queueSize($queue),
            'stuck_sending' => $stuck,
            'overdue_pending' => $overdue,
            // Reservados sin procesar es la señal inequívoca de que el worker
            // no está consumiendo esta cola.
            'healthy' => $stuck === 0 && $overdue === 0,
        ];
    }

    /**
     * Trabajos esperando en la cola. null si no se puede consultar Redis (por
     * ejemplo con QUEUE_CONNECTION=sync en tests): no saberlo no es lo mismo
     * que estar sano, y quien lo consuma debe distinguirlo.
     */
    private function queueSize(string $queue): ?int
    {
        if (config('queue.default') !== 'redis') {
            return null;
        }

        try {
            $prefix = (string) config('database.redis.options.prefix', '');

            return (int) Redis::connection(config('queue.connections.redis.connection', 'default'))
                ->llen($prefix.'queues:'.$queue);
        } catch (Throwable) {
            return null;
        }
    }
}
