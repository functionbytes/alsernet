<?php

namespace Modules\HelpdeskErp\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskErp\Services\ErpContextService;

class WarmErpCacheJob implements ShouldQueue
{
    use Queueable;

    /**
     * Emails por job (ver el razonamiento del timeout en WarmErpCacheCommand).
     * Quien encole lotes debe partirlos con este tamaño: un lote mayor puede
     * reventar el timeout del job con el ERP caído y bloquear el worker.
     */
    public const EMAILS_PER_JOB = 3;

    public int $tries = 1;

    /**
     * 29-sep-2026: el job reventaba a diario por su propio timeout (60s). Con
     * Oracle directo (ErpContextService::fetchDirectFromOracle) un email cuesta
     * ~14s si no existe (CLIENTE_CENT.EMAIL sin índice, full scan) y ~25-30s si
     * existe (búsqueda + pedidos + contacto), y oci_set_call_timeout no está
     * disponible con este Oracle Client: 3 emails podían pasar de 60s. Además,
     * la API manda hasta 50 emails en un solo job.
     *
     * Ahora el job tiene un presupuesto de tiempo (BUDGET_SECONDS): solo empieza
     * un email nuevo si queda margen, y los que no le caben los reencola en un
     * job nuevo. El resultado (qué se cachea) es el mismo. El timeout queda con
     * margen para el peor email que empiece justo antes del límite y por debajo
     * de retry_after de la conexión redis (360s) y del timeout del supervisor de
     * Horizon (120s), para que nunca haya doble ejecución.
     */
    public int $timeout = 120;

    public int $backoff = 30;

    /**
     * Segundos tras los que no se empieza un email nuevo en este job.
     */
    public const BUDGET_SECONDS = 50;

    public function __construct(
        private readonly array $emails
    ) {
        $this->onQueue('helpdesk-erp-warming');
    }

    public function handle(ErpContextService $service): void
    {
        $start = microtime(true);
        $emails = array_values($this->emails);

        foreach ($emails as $i => $email) {
            if ($i > 0 && (microtime(true) - $start) >= self::BUDGET_SECONDS) {
                // Sin presupuesto: lo pendiente va a otro job (mismo resultado,
                // sin reventar el timeout).
                self::dispatch(array_slice($emails, $i));

                return;
            }

            try {
                $service->getCustomerContext($email);
            } catch (\Throwable) {
                // Mejor esfuerzo — ignorar errores individuales por email
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('WarmErpCacheJob failed', [
            'email_count' => count($this->emails),
            'error' => $e->getMessage(),
        ]);
    }
}
