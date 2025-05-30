<?php

namespace App\Services\Return;

use App\Models\Return\ReturnRequest;
use App\Models\Return\ReturnStatus;
use App\Models\Return\ReturnHistory;
use App\Services\Return\ReturnPDFService;
use App\Services\Return\ReturnEmailService;

// Events
use App\Events\Return\ReturnCreated;
use App\Events\Return\ReturnStatusChanged;
use App\Events\Return\ReturnCompleted;
use App\Events\Return\ReturnPaymentProcessed;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReturnService
{
    protected $pdfService;
    protected $emailService;

    public function __construct(ReturnPDFService $pdfService, ReturnEmailService $emailService)
    {
        $this->pdfService = $pdfService;
        $this->emailService = $emailService;
    }

    /**
     * Crear nueva solicitud de devolución
     */
    public function createReturnRequest(array $data): ReturnRequest
    {
        return DB::transaction(function () use ($data) {
            // Obtener el estado inicial por defecto
            $initialStatus = ReturnStatus::where('active', true)
                ->whereHas('state', function($q) {
                    $q->where('name', 'New');
                })
                ->first();

            if (!$initialStatus) {
                $initialStatus = ReturnStatus::where('active', true)->first();
                if (!$initialStatus) {
                    throw new \Exception('No hay estados de devolución activos configurados');
                }
            }

            // Crear la solicitud de devolución
            $return = ReturnRequest::create([
                'id_order' => $data['id_order'],
                'id_customer' => $data['id_customer'] ?? 0,
                'id_address' => $data['id_address'] ?? 0,
                'id_order_detail' => $data['id_order_detail'],
                'id_return_status' => $initialStatus->id_return_status,
                'id_return_type' => $data['id_return_type'],
                'description' => $data['description'],
                'id_return_reason' => $data['id_return_reason'],
                'product_quantity' => $data['product_quantity'],
                'product_quantity_reinjected' => 0,
                'received_date' => now(),
                'pickup_date' => $data['pickup_date'] ?? null,
                'pickup_selection' => $data['pickup_selection'] ?? 0,
                'is_refunded' => false,
                'is_wallet_used' => 0,
                'id_shop' => $data['id_shop'] ?? 1,
                'return_address' => $data['return_address'] ?? null,
                'customer_name' => $data['customer_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'iban' => $data['iban'] ?? null,
                'logistics_mode' => $data['logistics_mode'],
                'created_by' => $data['created_by'] ?? 'web'
            ]);

            // Cargar relaciones necesarias
            $return->load(['status.state', 'returnType', 'returnReason']);

            // *** DISPARAR EVENTO: Devolución Creada ***
            event(new ReturnCreated(
                $return,
                $data['created_by'] ?? 'web',
                request()->ip(),
                request()->userAgent()
            ));

            Log::info('Return request created successfully', [
                'return_id' => $return->id_return_request,
                'order_id' => $return->id_order,
                'created_by' => $data['created_by'] ?? 'web'
            ]);

            return $return;
        });
    }

    /**
     * Actualizar estado de devolución
     */
    public function updateReturnStatus($returnId, $newStatusId, $description = '', $employeeId = 1, array $metadata = []): ReturnRequest
    {
        return DB::transaction(function () use ($returnId, $newStatusId, $description, $employeeId, $metadata) {
            $return = ReturnRequest::with(['status.state'])->findOrFail($returnId);
            $previousStatus = $return->status;
            $newStatus = ReturnStatus::with(['state'])->findOrFail($newStatusId);

            // Validar transición de estado
            if (!$this->isValidStatusTransition($return->id_return_status, $newStatusId)) {
                throw new \Exception('Transición de estado no válida');
            }

            // Actualizar estado en la solicitud
            $return->update(['id_return_status' => $newStatusId]);

            // Recargar para obtener el nuevo estado
            $return->refresh();
            $return->load(['status.state', 'returnType', 'returnReason']);

            // *** DISPARAR EVENTO: Estado Cambiado ***
            event(new ReturnStatusChanged(
                $return,
                $previousStatus,
                $newStatus,
                $employeeId,
                $description,
                $metadata
            ));

            // Verificar si la devolución se completó
            if ($this->isReturnCompleted($newStatus)) {
                $this->handleReturnCompletion($return, $newStatus, $employeeId, $metadata);
            }

            Log::info('Return status updated successfully', [
                'return_id' => $returnId,
                'previous_status' => $previousStatus->id_return_status,
                'new_status' => $newStatusId,
                'updated_by' => $employeeId
            ]);

            return $return;
        });
    }

    /**
     * Procesar pago/reembolso
     */
    public function processPayment(ReturnRequest $return, array $paymentData): \App\Models\Return\ReturnPayment
    {
        return DB::transaction(function () use ($return, $paymentData) {
            $payment = \App\Models\Return\ReturnPayment::create([
                'id_return_request' => $return->id_return_request,
                'amount' => $paymentData['amount'],
                'payment_method' => $paymentData['payment_method'],
                'transaction_id' => $paymentData['transaction_id'] ?? null,
                'payment_status' => $paymentData['status'] ?? 'completed',
                'processed_at' => now(),
                'notes' => $paymentData['notes'] ?? null,
                'id_employee' => $paymentData['employee_id'] ?? 1
            ]);

            // *** DISPARAR EVENTO: Pago Procesado ***
            event(new ReturnPaymentProcessed(
                $return,
                $payment,
                $paymentData['employee_id'] ?? 1,
                $paymentData['metadata'] ?? []
            ));

            Log::info('Return payment processed', [
                'return_id' => $return->id_return_request,
                'payment_id' => $payment->id_return_payment,
                'amount' => $payment->amount,
                'method' => $payment->payment_method,
                'status' => $payment->payment_status
            ]);

            return $payment;
        });
    }

    /**
     * Manejar finalización de devolución
     */
    private function handleReturnCompletion(ReturnRequest $return, ReturnStatus $finalStatus, int $employeeId, array $metadata): void
    {
        // Determinar el tipo de finalización
        $completionType = $this->determineCompletionType($return, $finalStatus);

        // Calcular monto total si es reembolso
        $totalAmount = 0.0;
        if ($completionType === 'refund') {
            $totalAmount = $return->payments()
                ->where('payment_status', 'completed')
                ->sum('amount');
        }

        // *** DISPARAR EVENTO: Devolución Completada ***
        event(new ReturnCompleted(
            $return,
            $completionType,
            $employeeId,
            $totalAmount,
            array_merge($metadata, [
                'final_status_id' => $finalStatus->id_return_status,
                'processing_start' => $return->created_at->toISOString(),
                'processing_end' => now()->toISOString()
            ])
        ));
    }

    /**
     * Determinar el tipo de finalización
     */
    private function determineCompletionType(ReturnRequest $return, ReturnStatus $finalStatus): string
    {
        // Lógica para determinar si es refund, replacement o repair
        // basada en el tipo de devolución y el estado final

        if ($finalStatus->is_refunded) {
            return 'refund';
        }

        // Determinar por el tipo de devolución original
        switch ($return->id_return_type) {
            case 1: // Reembolso
                return 'refund';
            case 2: // Reemplazo
                return 'replacement';
            case 3: // Reparación
                return 'repair';
            default:
                return 'refund';
        }
    }

    /**
     * Verificar si la devolución está completada
     */
    private function isReturnCompleted(ReturnStatus $status): bool
    {
        return $status->state->name === 'Close' && $status->active;
    }

    /**
     * Crear entrada en historial (ya no se usa directamente, lo manejan los listeners)
     */
    protected function createHistoryEntry($returnId, $statusId, $description, $employeeId, $setPickup = false, $isRefunded = false): void
    {
        // Este método ahora es manejado por UpdateHistoryListener
        // Mantenido para compatibilidad hacia atrás
    }

    /**
     * Validar transición de estados
     */
    public function isValidStatusTransition($currentStatusId, $newStatusId): bool
    {
        if ($currentStatusId == $newStatusId) {
            return false;
        }

        $currentStatus = ReturnStatus::find($currentStatusId);
        $newStatus = ReturnStatus::find($newStatusId);

        if (!$currentStatus || !$newStatus) {
            return false;
        }

        // Lógica de transiciones válidas basada en los estados
        $validTransitions = [
            1 => [2, 5, 9], // New -> Verification, Waiting for package, Pending
            2 => [3, 4, 6, 8], // Verification -> Negotiation, Package received, Declined, Pickup
            3 => [4, 7, 10, 11], // Negotiation -> Resolved, Completed, Replaced, Repaired
            4 => [7], // Resolved -> Completed
            5 => [] // Close (estado final)
        ];

        return in_array($newStatus->id_return_state, $validTransitions[$currentStatus->id_return_state] ?? []);
    }

    /**
     * Verificar si la devolución está aprobada
     */
    public function isReturnApproved($returnId): bool
    {
        $approvedStatusId = config('returns.approved_status_id', 2);

        return ReturnHistory::where('id_return_request', $returnId)
            ->where('id_return_status', $approvedStatusId)
            ->exists();
    }

    /**
     * Obtener estadísticas de devoluciones
     */
    public function getReturnStatistics(): array
    {
        return [
            'total_requests' => ReturnRequest::count(),
            'pending_requests' => ReturnRequest::pending()->count(),
            'approved_requests' => ReturnRequest::approved()->count(),
            'completed_requests' => ReturnRequest::completed()->count(),
            'refunded_requests' => ReturnRequest::refunded()->count(),
            'by_return_type' => ReturnRequest::selectRaw('id_return_type, COUNT(*) as count')
                ->with('returnType')
                ->groupBy('id_return_type')
                ->get()
                ->map(function($item) {
                    return [
                        'type' => $item->returnType->getTranslation()->name ?? 'Desconocido',
                        'count' => $item->count
                    ];
                }),
            'by_logistics_mode' => ReturnRequest::selectRaw('logistics_mode, COUNT(*) as count')
                ->groupBy('logistics_mode')
                ->get()
                ->pluck('count', 'logistics_mode'),
            'monthly_trend' => ReturnRequest::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
                ->where('created_at', '>=', now()->subMonths(12))
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get()
        ];
    }

    /**
     * Obtener datos completos para PDF
     */
    public function getReturnDataForPDF($returnId): array
    {
        $return = ReturnRequest::with(['status', 'returnType', 'returnReason'])->findOrFail($returnId);

        return [
            'return' => $return,
            'request_date' => $return->created_at->format('d/m/Y'),
            'pickup_date' => $return->pickup_date ? $return->pickup_date->format('d/m/Y') : 'No establecida',
            'status_name' => $return->getStatusName(),
            'return_type_name' => $return->getReturnTypeName(),
            'return_reason_name' => $return->getReturnReasonName(),
            'logistics_mode_label' => $return->getLogisticsModeLabel(),
            'is_approved' => $this->isReturnApproved($returnId),
            'company_info' => config('returns.company_info', []),
            'custom_content' => config('returns.pdf_content', '')
        ];
    }

    /**
     * Crear devolución con eventos deshabilitados (para migraciones/seeders)
     */
    public function createReturnRequestSilent(array $data): ReturnRequest
    {
        return DB::transaction(function () use ($data) {
            // Obtener el estado inicial por defecto
            $initialStatus = ReturnStatus::where('active', true)
                ->whereHas('state', function($q) {
                    $q->where('name', 'New');
                })
                ->first();

            if (!$initialStatus) {
                $initialStatus = ReturnStatus::where('active', true)->first();
                if (!$initialStatus) {
                    throw new \Exception('No hay estados de devolución activos configurados');
                }
            }

            // Crear sin disparar eventos
            $return = ReturnRequest::withoutEvents(function () use ($data, $initialStatus) {
                return ReturnRequest::create([
                    'id_order' => $data['id_order'],
                    'id_customer' => $data['id_customer'] ?? 0,
                    'id_address' => $data['id_address'] ?? 0,
                    'id_order_detail' => $data['id_order_detail'],
                    'id_return_status' => $initialStatus->id_return_status,
                    'id_return_type' => $data['id_return_type'],
                    'description' => $data['description'],
                    'id_return_reason' => $data['id_return_reason'],
                    'product_quantity' => $data['product_quantity'],
                    'product_quantity_reinjected' => 0,
                    'received_date' => now(),
                    'pickup_date' => $data['pickup_date'] ?? null,
                    'pickup_selection' => $data['pickup_selection'] ?? 0,
                    'is_refunded' => false,
                    'is_wallet_used' => 0,
                    'id_shop' => $data['id_shop'] ?? 1,
                    'return_address' => $data['return_address'] ?? null,
                    'customer_name' => $data['customer_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'iban' => $data['iban'] ?? null,
                    'logistics_mode' => $data['logistics_mode'],
                    'created_by' => $data['created_by'] ?? 'silent'
                ]);
            });

            return $return->load(['status.state', 'returnType', 'returnReason']);
        });
    }

    /**
     * Batch update de estados (útil para procesos masivos)
     */
    public function batchUpdateStatus(array $returnIds, int $newStatusId, string $description = '', int $employeeId = 1): array
    {
        $results = [];

        foreach ($returnIds as $returnId) {
            try {
                $result = $this->updateReturnStatus($returnId, $newStatusId, $description, $employeeId, [
                    'batch_operation' => true,
                    'batch_size' => count($returnIds)
                ]);

                $results[] = [
                    'return_id' => $returnId,
                    'status' => 'success',
                    'result' => $result
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'return_id' => $returnId,
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];

                Log::error('Batch status update failed for return', [
                    'return_id' => $returnId,
                    'new_status_id' => $newStatusId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Obtener métricas de rendimiento del sistema
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'avg_processing_time' => $this->getAverageProcessingTime(),
            'completion_rate' => $this->getCompletionRate(),
            'customer_satisfaction' => $this->getCustomerSatisfactionScore(),
            'sla_compliance' => $this->getSLAComplianceRate(),
            'top_failure_reasons' => $this->getTopFailureReasons(),
            'processing_efficiency' => $this->getProcessingEfficiency()
        ];
    }

    /**
     * Métodos auxiliares para métricas
     */
    private function getAverageProcessingTime(): float
    {
        return ReturnRequest::completed()
            ->selectRaw('AVG(DATEDIFF(updated_at, created_at)) as avg_days')
            ->value('avg_days') ?? 0;
    }

    private function getCompletionRate(): float
    {
        $total = ReturnRequest::count();
        $completed = ReturnRequest::completed()->count();

        return $total > 0 ? ($completed / $total) * 100 : 0;
    }

    private function getCustomerSatisfactionScore(): float
    {
        // Lógica basada en tiempo de procesamiento, rechazos, etc.
        $fastProcessing = ReturnRequest::completed()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $totalRecent = ReturnRequest::where('created_at', '>=', now()->subDays(30))->count();

        return $totalRecent > 0 ? ($fastProcessing / $totalRecent) * 100 : 0;
    }

    private function getSLAComplianceRate(): float
    {
        $slaLimit = 7; // días

        $withinSLA = ReturnRequest::completed()
            ->whereRaw('DATEDIFF(updated_at, created_at) <= ?', [$slaLimit])
            ->count();

        $totalCompleted = ReturnRequest::completed()->count();

        return $totalCompleted > 0 ? ($withinSLA / $totalCompleted) * 100 : 0;
    }

    private function getTopFailureReasons(): array
    {
        return ReturnRequest::whereHas('status', function($q) {
            $q->where('color', '#dc3545'); // Estados rechazados
        })
            ->selectRaw('id_return_reason, COUNT(*) as count')
            ->with('returnReason')
            ->groupBy('id_return_reason')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'reason' => $item->returnReason->getTranslation()->name ?? 'Desconocido',
                    'count' => $item->count
                ];
            })
            ->toArray();
    }

    private function getProcessingEfficiency(): array
    {
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        return [
            'returns_processed_today' => ReturnRequest::whereDate('updated_at', $today)->count(),
            'returns_processed_yesterday' => ReturnRequest::whereDate('updated_at', $yesterday)->count(),
            'avg_daily_processing' => ReturnRequest::where('updated_at', '>=', now()->subDays(30))
                    ->selectRaw('COUNT(*) / 30 as avg_daily')
                    ->value('avg_daily') ?? 0
        ];
    }
}
