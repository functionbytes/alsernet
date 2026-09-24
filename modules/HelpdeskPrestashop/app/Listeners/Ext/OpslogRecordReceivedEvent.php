<?php

namespace Modules\HelpdeskPrestashop\Listeners\Ext;

use Illuminate\Support\Facades\Log;
use Modules\HelpdeskPrestashop\Services\Ext\OpslogEventStore;

/**
 * Deja constancia de cada evento que PrestaShop manda al webhook (pieza 38).
 *
 * Síncrono a propósito: corre dentro de la petición del webhook, que es la
 * única que conoce la clave de deduplicación del envío. Nunca lanza: si esta
 * fila no se puede guardar (tabla sin migrar, BD caída), el receptor debe
 * seguir contestando 200 — un 500 haría que PS reintentase y repitiese los
 * demás listeners por culpa de un registro de operación.
 */
class OpslogRecordReceivedEvent
{
    public function __construct(
        private readonly OpslogEventStore $store
    ) {}

    public function handle(object $event): void
    {
        try {
            if (! $this->store->available()) {
                return;
            }

            $name = $this->store->eventName($event);
            $payload = property_exists($event, 'payload') && is_array($event->payload) ? $event->payload : [];

            if ($name === null || in_array($name, (array) config('helpdeskprestashop.ext.opslog.events.ignore', []), true)) {
                return;
            }

            // Repetición desde "Reprocesar": la fila ya existe y la actualiza
            // OpslogEventStore::reprocess(); no se crea otra.
            if (OpslogEventStore::$replayingId !== null) {
                return;
            }

            $this->store->record($name, $payload, $this->dedupKey());

            if (random_int(1, 200) === 1) {
                $this->store->prune((int) config('helpdeskprestashop.ext.opslog.events.retention_days', 30));
            }
        } catch (\Throwable $e) {
            Log::warning('OpslogRecordReceivedEvent: no se pudo registrar el evento', [
                'event' => $event::class,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Misma clave que calcula PsEventReceiverController para deduplicar. Solo
     * existe cuando el evento viene del webhook; fuera de él (tests, consola)
     * cada evento es una fila.
     */
    private function dedupKey(): ?string
    {
        $request = request();

        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return null;
        }

        if ($request === null || ! $request->hasHeader('X-Alsernet-Event')) {
            return null;
        }

        $timestamp = (int) $request->header('X-Alsernet-Timestamp', 0);
        $signature = (string) $request->header('X-Alsernet-Signature', '');
        $key = $request->header('X-Alsernet-Idempotency-Key') ?: md5($timestamp.':'.$signature);

        return mb_substr((string) $key, 0, 64);
    }
}
