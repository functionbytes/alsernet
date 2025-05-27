<?php

namespace App\Services\Return;

use App\Models\Return\ReturnRequest;
use App\Models\Return\ReturnStatus;
use App\Models\Return\ReturnHistory;
use App\Services\Return\ReturnPDFService;
use App\Services\Return\ReturnEmailService;
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
                // Usar el primer estado activo disponible
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

            // Crear entrada en historial
            $this->createHistoryEntry(
                $return->id_return_request,
                $initialStatus->id_return_status,
                'Solicitud de devolución creada',
                $data['id_employee'] ?? 1
            );

            // Generar PDF
            try {
                $pdfPath = $this->pdfService->generateAndSaveReturnPDF($return);
                $return->update(['pdf_path' => $pdfPath]);
            } catch (\Exception $e) {
                Log::warning('Error generando PDF para devolución ' . $return->id_return_request . ': ' . $e->getMessage());
            }

            // Enviar email de confirmación
            try {
                if (in_array($data['created_by'] ?? 'web', ['admin', 'callcenter'])) {
                    $this->emailService->sendReturnConfirmation($return);
                }
            } catch (\Exception $e) {
                Log::warning('Error enviando email para devolución ' . $return->id_return_request . ': ' . $e->getMessage());
            }

            return $return->load(['status.state', 'returnType', 'returnReason']);
        });
    }

    /**
     * Actualizar estado de devolución
     */
    public function updateReturnStatus($returnId, $newStatusId, $description = '', $employeeId = 1): ReturnRequest
    {
        return DB::transaction(function () use ($returnId, $newStatusId, $description, $employeeId) {
            $return = ReturnRequest::findOrFail($returnId);
            $newStatus = ReturnStatus::findOrFail($newStatusId);

            // Validar transición de estado
            if (!$this->isValidStatusTransition($return->id_return_status, $newStatusId)) {
                throw new \Exception('Transición de estado no válida');
            }

            // Actualizar estado en la solicitud
            $return->update(['id_return_status' => $newStatusId]);

            // Crear entrada en historial
            $this->createHistoryEntry(
                $returnId,
                $newStatusId,
                $description ?: 'Estado actualizado a ' . ($newStatus->getTranslation()->name ?? 'Desconocido'),
                $employeeId,
                $newStatus->is_pickup ?? false,
                $newStatus->is_refunded ?? false
            );

            // Actualizar campos relacionados al estado
            if ($newStatus->is_refunded) {
                $return->update(['is_refunded' => true]);
            }

            // Enviar email si está configurado
            try {
                if ($newStatus->send_email) {
                    $this->emailService->sendStatusUpdateNotification($return);
                }
            } catch (\Exception $e) {
                Log::warning('Error enviando email de actualización para devolución ' . $return->id_return_request . ': ' . $e->getMessage());
            }

            return $return->load(['status.state', 'returnType', 'returnReason']);
        });
    }

    /**
     * Crear entrada en historial
     */
    protected function createHistoryEntry($returnId, $statusId, $description, $employeeId, $setPickup = false, $isRefunded = false): void
    {
        ReturnHistory::create([
            'id_return_request' => $returnId,
            'id_return_status' => $statusId,
            'description' => $description,
            'id_employee' => $employeeId,
            'set_pickup' => $setPickup,
            'is_refunded' => $isRefunded,
            'shown_to_customer' => true
        ]);
    }

    /**
     * Validar transición de estados
     */
    public function isValidStatusTransition($currentStatusId, $newStatusId): bool
    {
        if ($currentStatusId == $newStatusId) {
            return false; // No cambiar al mismo estado
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
}
