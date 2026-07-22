<?php

namespace Modules\HelpdeskCompliance\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Services\AuditLogService;
use Modules\HelpdeskCompliance\Models\ComplianceRequest;
use Modules\HelpdeskCompliance\Services\Handlers\ChatflowComplianceHandler;
use Modules\HelpdeskCompliance\Services\Handlers\DocumentComplianceHandler;
use Modules\HelpdeskCompliance\Services\Handlers\TicketComplianceHandler;
use Nwidart\Modules\Facades\Module;

/**
 * Cascada GDPR desacoplada de la request HTTP del admin que dispara el
 * borrado. Recibe solo datos planos (no el modelo Customer): en modo hard
 * el Customer ya fue borrado por completo antes de encolarse, y rehidratar
 * un modelo inexistente al deserializar el job fallaria.
 */
class ProcessComplianceCascadeJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public int $backoff = 30;

    /**
     * $customerEmail / $customerPhones vienen capturados ANTES del borrado core
     * (el Customer ya está anonimizado o eliminado cuando corre este job) y son
     * las claves de match de los expedientes de HelpdeskDocument. Opcionales
     * para no romper jobs ya serializados en cola de versiones anteriores.
     *
     * @param  array<int, int>  $conversationIds
     * @param  array{deleted: int, anonymized: int}  $coreResult
     * @param  array<int, string>  $customerPhones
     */
    public function __construct(
        private readonly int $customerId,
        private readonly bool $hard,
        private readonly array $conversationIds,
        private readonly array $coreResult,
        private readonly ?int $requestedBy,
        private readonly ?string $customerEmail = null,
        private readonly array $customerPhones = [],
    ) {
        $this->onQueue('helpdeskcompliance');
    }

    public function handle(): void
    {
        $summaries = [];

        DB::connection('helpdesk')->transaction(function () use (&$summaries): void {
            if ($this->moduleReady('HelpdeskTickets', 'Modules\\HelpdeskTickets\\Models\\Ticket')) {
                $summaries[] = app(TicketComplianceHandler::class)
                    ->handle($this->customerId, $this->hard, $this->conversationIds);
            }

            if ($this->moduleReady('HelpdeskChatFlow', 'Modules\\HelpdeskChatFlow\\Models\\ChatFlowSession')) {
                $summaries[] = app(ChatflowComplianceHandler::class)
                    ->handle($this->customerId, $this->hard, $this->conversationIds);
            }

            // Los expedientes viven en el módulo Document (los datos y adjuntos
            // KYC persisten aunque el puente HelpdeskDocument esté deshabilitado),
            // por eso el guard comprueba 'Document' y no 'HelpdeskDocument'.
            if ($this->moduleReady('Document', 'Modules\\Document\\Entities\\Document')) {
                $summaries[] = app(DocumentComplianceHandler::class)
                    ->handle($this->customerEmail, $this->customerPhones, $this->hard);
            }
        });

        $request = ComplianceRequest::create([
            'customer_id' => $this->customerId,
            'type' => $this->hard ? ComplianceRequest::TYPE_DELETE_HARD : ComplianceRequest::TYPE_DELETE_SOFT,
            'status' => 'completed',
            'requested_by' => $this->requestedBy,
            'modules_affected' => array_values(array_map(fn (array $s): string => $s['module'], $summaries)),
            'result_summary' => [
                'core' => $this->coreResult,
                'handlers' => $summaries,
            ],
            'completed_at' => now(),
        ]);

        AuditLogService::record('gdpr.cascade.completed', $request, [], [
            'mode' => $this->hard ? 'hard' : 'soft',
            'handlers' => $summaries,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessComplianceCascadeJob failed', [
            'customer_id' => $this->customerId,
            'hard' => $this->hard,
            'error' => $exception->getMessage(),
        ]);

        $request = ComplianceRequest::create([
            'customer_id' => $this->customerId,
            'type' => $this->hard ? ComplianceRequest::TYPE_DELETE_HARD : ComplianceRequest::TYPE_DELETE_SOFT,
            'status' => 'failed',
            'requested_by' => $this->requestedBy,
            'modules_affected' => [],
            'result_summary' => [
                'core' => $this->coreResult,
                'error' => $exception->getMessage(),
            ],
            'completed_at' => now(),
        ]);

        // Un fallo de la cascada GDPR es legalmente relevante: debe quedar en el
        // audit trail, no solo en los logs de la aplicación (igual que el éxito
        // registra 'gdpr.cascade.completed' en handle()).
        AuditLogService::record('gdpr.cascade.failed', $request, [], [
            'mode' => $this->hard ? 'hard' : 'soft',
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * A PROPÓSITO comprueba solo que el módulo esté instalado/habilitado y su
     * modelo exista — NO el toggle de integración (helpdesk_tickets_enabled()
     * / helpdesk_chatflow_enabled()). Esto es una cascada de borrado GDPR
     * (obligación legal): la PII del cliente debe erradicarse allá donde viva,
     * aunque un admin haya desactivado ese módulo en Settings > Integraciones.
     * Saltarse el borrado por un switch de UI sería una violación de GDPR, no
     * una mejora — no atar esto al toggle.
     */
    private function moduleReady(string $module, string $fqcn): bool
    {
        return (Module::find($module)?->isEnabled() ?? false) && class_exists($fqcn);
    }
}
