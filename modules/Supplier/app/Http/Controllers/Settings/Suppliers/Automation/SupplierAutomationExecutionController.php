<?php

namespace Modules\Supplier\Http\Controllers\Settings\Suppliers\Automation;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Models\Automation\AutomationExecution;
use Modules\Supplier\Services\AutomationOrchestrationService;

class SupplierAutomationExecutionController extends Controller
{
    public function __construct(protected AutomationOrchestrationService $orchestrationService) {}

    /**
     * Get execution details
     */
    public function show(string $uid): JsonResponse
    {
        try {
            $execution = AutomationExecution::where('uid', $uid)
                ->with(['workflow', 'supplier', 'source'])
                ->firstOrFail();

            $statusLabels = [
                'pending' => 'Pendiente', 'queued' => 'En cola', 'running' => 'Ejecutando',
                'completed' => 'Completado', 'failed' => 'Fallido', 'timeout' => 'Timeout', 'cancelled' => 'Cancelado',
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'uid' => $execution->uid,
                    'workflow_name' => $execution->workflow?->name ?? '—',
                    'supplier_name' => $execution->supplier?->name ?? '—',
                    'status' => $execution->status,
                    'status_label' => $statusLabels[$execution->status] ?? $execution->status,
                    'trigger_type' => $execution->trigger_type,
                    'started_at' => $execution->started_at?->format('d/m/Y H:i:s'),
                    'completed_at' => $execution->completed_at?->format('d/m/Y H:i:s'),
                    'duration_ms' => $execution->duration_ms,
                    'retry_count' => $execution->retry_count,
                    'items_processed' => $execution->items_processed,
                    'items_succeeded' => $execution->items_succeeded,
                    'items_failed' => $execution->items_failed,
                    'output_data' => $execution->output_data,
                    'error_details' => $execution->error_details,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting execution details: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los detalles de la ejecución',
            ], 500);
        }
    }

    /**
     * Retry a failed execution
     */
    public function retry(string $uid): JsonResponse
    {
        try {
            $execution = AutomationExecution::where('uid', $uid)->firstOrFail();

            if ($execution->status !== 'failed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden reintentar ejecuciones fallidas',
                ], 400);
            }

            $result = $this->orchestrationService->retryExecution($execution);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'execution' => $result['execution'] ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrying execution: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al reintentar la ejecución',
            ], 500);
        }
    }

    /**
     * Cancel a running or pending execution
     */
    public function cancel(string $uid): JsonResponse
    {
        try {
            $execution = AutomationExecution::where('uid', $uid)->firstOrFail();

            if (! in_array($execution->status, ['pending', 'running'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden cancelar ejecuciones pendientes o en ejecución',
                ], 400);
            }

            $result = $this->orchestrationService->cancelExecution($execution);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
            ]);

        } catch (\Exception $e) {
            Log::error('Error canceling execution: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar la ejecución',
            ], 500);
        }
    }

    /**
     * Clear all failed executions
     */
    public function clearFailed(): JsonResponse
    {
        try {
            $count = AutomationExecution::where('status', 'failed')->delete();

            return response()->json([
                'success' => true,
                'message' => "Se eliminaron {$count} ejecuciones fallidas",
            ]);

        } catch (\Exception $e) {
            Log::error('Error clearing failed executions: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar ejecuciones fallidas',
            ], 500);
        }
    }
}
