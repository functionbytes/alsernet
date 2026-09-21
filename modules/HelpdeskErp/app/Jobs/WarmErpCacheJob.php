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

    // Debe quedar por debajo del retry_after (90s) de la conexión de cola
    // redis: si el timeout iguala o supera retry_after, el worker puede
    // considerar el job "perdido" y otro worker lo recoge mientras el
    // primero sigue procesándolo — doble ejecución (ver también
    // RefreshErpContextJob, mismo problema).
    public int $timeout = 60;

    public int $backoff = 30;

    public function __construct(
        private readonly array $emails
    ) {
        $this->onQueue('helpdesk-erp-warming');
    }

    public function handle(ErpContextService $service): void
    {
        foreach ($this->emails as $email) {
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
