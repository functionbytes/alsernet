<?php

namespace Modules\Attention\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Http\Requests\BulkActionRequest;
use Modules\Attention\Models\Attention;
use Modules\Attention\Services\AttentionNotificationService;
use Throwable;

/**
 * Bulk action endpoints — authentication required.
 */
class AttentionBulkApiController extends Controller
{
    public function __construct(
        protected ?AttentionNotificationService $notificationService = null
    ) {}

    /**
     * Bulk assign peticiones to department or user
     * POST /api/attentions/bulk-assign
     */
    public function bulkAssign(BulkActionRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $attentionIds = $request->attention_ids;
            $departmentId = $request->department_id;
            $userId = $request->user_id;

            $attentions = Attention::whereIn('id', $attentionIds)->get()->keyBy('id');
            $successCount = 0;
            $failureCount = 0;
            $errors = [];

            foreach ($attentionIds as $attentionId) {
                try {
                    $attention = $attentions->get($attentionId);

                    if (! $attention) {
                        $failureCount++;
                        $errors[] = "Atención ID {$attentionId}: No encontrada";

                        continue;
                    }

                    if ($attention->isClosed()) {
                        $failureCount++;
                        $errors[] = "Radicado {$attention->radicado}: Ya está cerrado";

                        continue;
                    }

                    if ($departmentId) {
                        $attention->assignToDepartment($departmentId, 'Asignación masiva');
                    }

                    if ($userId) {
                        $attention->assignTo($userId, 'Asignación masiva');
                    }

                    if ($attention->status === AttentionStatus::RECEIVED) {
                        $attention->changeStatus(AttentionStatus::IN_PROCESS, 'Asignación masiva');
                    }

                    if ($userId && $this->notificationService) {
                        try {
                            $this->notificationService->notifyAssigned($attention);
                        } catch (Throwable $e) {
                            Log::error('Failed to send bulk assignment notification', [
                                'radicado' => $attention->radicado,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    $successCount++;

                } catch (Throwable $e) {
                    $failureCount++;
                    $errors[] = "Atención ID {$attentionId}: {$e->getMessage()}";
                    Log::error('Error in bulk assign item', [
                        'attention_id' => $attentionId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Bulk assign completed', [
                'user_id' => auth()->id(),
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'department_id' => $departmentId,
                'assigned_user_id' => $userId,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Asignación masiva completada: {$successCount} exitosas, {$failureCount} fallidas",
                'data' => [
                    'success_count' => $successCount,
                    'failure_count' => $failureCount,
                    'total' => count($attentionIds),
                    'errors' => $errors,
                ],
            ]);

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Error in bulk assign', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al realizar asignación masiva',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Bulk close peticiones
     * POST /api/attentions/bulk-close
     */
    public function bulkClose(BulkActionRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $attentionIds = $request->attention_ids;
            $attentions = Attention::whereIn('id', $attentionIds)->get()->keyBy('id');
            $successCount = 0;
            $failureCount = 0;
            $errors = [];

            foreach ($attentionIds as $attentionId) {
                try {
                    $attention = $attentions->get($attentionId);

                    if (! $attention) {
                        $failureCount++;
                        $errors[] = "Atención ID {$attentionId}: No encontrada";

                        continue;
                    }

                    if ($attention->isClosed()) {
                        $failureCount++;
                        $errors[] = "Radicado {$attention->radicado}: Ya está cerrado";

                        continue;
                    }

                    if (! $attention->isResolved()) {
                        $failureCount++;
                        $errors[] = "Radicado {$attention->radicado}: Debe estar resuelto antes de cerrar";

                        continue;
                    }

                    $attention->close('Cierre masivo');
                    $successCount++;

                } catch (Throwable $e) {
                    $failureCount++;
                    $errors[] = "Atención ID {$attentionId}: {$e->getMessage()}";
                    Log::error('Error in bulk close item', [
                        'attention_id' => $attentionId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Bulk close completed', [
                'user_id' => auth()->id(),
                'success_count' => $successCount,
                'failure_count' => $failureCount,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Cierre masivo completado: {$successCount} exitosas, {$failureCount} fallidas",
                'data' => [
                    'success_count' => $successCount,
                    'failure_count' => $failureCount,
                    'total' => count($attentionIds),
                    'errors' => $errors,
                ],
            ]);

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Error in bulk close', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al realizar cierre masivo',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Bulk delete peticiones (soft delete)
     * DELETE /api/attentions/bulk-delete
     */
    public function bulkDelete(BulkActionRequest $request): JsonResponse
    {
        if (! auth()->user()->hasRole('super-settings')) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para realizar eliminación masiva',
            ], 403);
        }

        try {
            DB::beginTransaction();

            $attentionIds = $request->attention_ids;
            $attentions = Attention::whereIn('id', $attentionIds)->get()->keyBy('id');
            $successCount = 0;
            $failureCount = 0;
            $errors = [];

            foreach ($attentionIds as $attentionId) {
                try {
                    $attention = $attentions->get($attentionId);

                    if (! $attention) {
                        $failureCount++;
                        $errors[] = "Atención ID {$attentionId}: No encontrada";

                        continue;
                    }

                    Log::warning('Attention soft deleted via bulk action', [
                        'attention_id' => $attention->id,
                        'radicado' => $attention->radicado,
                        'deleted_by' => auth()->id(),
                    ]);

                    $attention->delete();
                    $successCount++;

                } catch (Throwable $e) {
                    $failureCount++;
                    $errors[] = "Atención ID {$attentionId}: {$e->getMessage()}";
                    Log::error('Error in bulk delete item', [
                        'attention_id' => $attentionId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::warning('Bulk delete completed', [
                'user_id' => auth()->id(),
                'success_count' => $successCount,
                'failure_count' => $failureCount,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Eliminación masiva completada: {$successCount} exitosas, {$failureCount} fallidas",
                'data' => [
                    'success_count' => $successCount,
                    'failure_count' => $failureCount,
                    'total' => count($attentionIds),
                    'errors' => $errors,
                ],
            ]);

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Error in bulk delete', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al realizar eliminación masiva',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }
}
