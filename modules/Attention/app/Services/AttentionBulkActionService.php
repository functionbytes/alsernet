<?php

namespace Modules\Attention\Services;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Events\AttentionAssigned;
use Modules\Attention\Events\AttentionClosed;
use Modules\Attention\Events\AttentionStatusChanged;
use Modules\Attention\Models\Attention;

/**
 * Servicio de acciones masivas para peticiones
 *
 * Gestiona operaciones en lote sobre múltiples peticiones incluyendo asignación,
 * cierre, eliminación y cambios de estado con logging y notificaciones.
 */
class AttentionBulkActionService
{
    /**
     * Asigna múltiples peticiones a departamento/usuario
     *
     * @param  array  $attentionIds  IDs de peticiones a asignar
     * @param  int|null  $departmentId  ID del departamento (opcional)
     * @param  int|null  $userId  ID del usuario (opcional)
     * @param  int|null  $performedBy  ID del usuario que realiza la acción
     * @return array Resultado con contadores de éxito/fallos
     *
     * @throws Exception Si los parámetros son inválidos
     */
    public static function bulkAssign(
        array $attentionIds,
        ?int $departmentId = null,
        ?int $userId = null,
        ?int $performedBy = null
    ): array {
        try {
            if (empty($attentionIds)) {
                throw new Exception('No se proporcionaron IDs de peticiones');
            }

            if (is_null($departmentId) && is_null($userId)) {
                throw new Exception('Debe proporcionar al menos un departamento o usuario');
            }

            $success = 0;
            $failed = 0;
            $errors = [];

            DB::beginTransaction();

            try {
                $attentions = Attention::whereIn('id', $attentionIds)->get();

                foreach ($attentions as $attention) {
                    try {
                        $updateData = [];

                        if (! is_null($departmentId)) {
                            $updateData['department_id'] = $departmentId;
                        }

                        if (! is_null($userId)) {
                            $updateData['assigned_to'] = $userId;
                        }

                        $updateData['updated_at'] = now();

                        $attention->update($updateData);

                        // Disparar evento de asignación
                        event(new AttentionAssigned($attention, $userId, $departmentId));

                        $success++;
                    } catch (Exception $e) {
                        $failed++;
                        $errors[] = [
                            'attention_id' => $attention->id,
                            'radicado' => $attention->radicado,
                            'error' => $e->getMessage(),
                        ];
                        Log::error('Bulk assign failed for attention', [
                            'attention_id' => $attention->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                DB::commit();

                // Log de acción masiva
                self::logBulkAction('assign', $attentionIds, [
                    'department_id' => $departmentId,
                    'user_id' => $userId,
                ], $performedBy);

                // Enviar notificaciones
                if ($success > 0) {
                    self::sendBulkNotifications($attentions->whereIn('id', $attentionIds), 'assign');
                }
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

            return self::buildResult($success, $failed, $errors);
        } catch (Exception $e) {
            Log::error('Bulk assign operation failed', ['error' => $e->getMessage()]);
            throw new Exception('Error en asignación masiva: '.$e->getMessage());
        }
    }

    /**
     * Cierra múltiples peticiones
     *
     * @param  array  $attentionIds  IDs de peticiones a cerrar
     * @param  string  $reason  Razón del cierre
     * @param  int|null  $performedBy  ID del usuario que realiza la acción
     * @return array Resultado con contadores de éxito/fallos
     *
     * @throws Exception Si los parámetros son inválidos
     */
    public static function bulkClose(
        array $attentionIds,
        string $reason,
        ?int $performedBy = null
    ): array {
        try {
            if (empty($attentionIds)) {
                throw new Exception('No se proporcionaron IDs de peticiones');
            }

            if (empty(trim($reason))) {
                throw new Exception('Debe proporcionar una razón para el cierre');
            }

            $success = 0;
            $failed = 0;
            $errors = [];

            DB::beginTransaction();

            try {
                $attentions = Attention::whereIn('id', $attentionIds)->get();

                foreach ($attentions as $attention) {
                    try {
                        // Verificar que no esté ya cerrada
                        if ($attention->status === AttentionStatus::CLOSED) {
                            $failed++;
                            $errors[] = [
                                'attention_id' => $attention->id,
                                'radicado' => $attention->radicado,
                                'error' => 'peticiones ya está cerrada',
                            ];

                            continue;
                        }

                        $attention->update([
                            'status' => AttentionStatus::CLOSED,
                            'closed_at' => now(),
                            'close_reason' => $reason,
                            'closed_by' => $performedBy,
                            'updated_at' => now(),
                        ]);

                        // Disparar evento de cierre
                        event(new AttentionClosed($attention, $reason));

                        $success++;
                    } catch (Exception $e) {
                        $failed++;
                        $errors[] = [
                            'attention_id' => $attention->id,
                            'radicado' => $attention->radicado,
                            'error' => $e->getMessage(),
                        ];
                        Log::error('Bulk close failed for attention', [
                            'attention_id' => $attention->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                DB::commit();

                // Log de acción masiva
                self::logBulkAction('close', $attentionIds, [
                    'reason' => $reason,
                ], $performedBy);

                // Enviar notificaciones
                if ($success > 0) {
                    self::sendBulkNotifications($attentions->whereIn('id', $attentionIds), 'close');
                }
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

            return self::buildResult($success, $failed, $errors);
        } catch (Exception $e) {
            Log::error('Bulk close operation failed', ['error' => $e->getMessage()]);
            throw new Exception('Error en cierre masivo: '.$e->getMessage());
        }
    }

    /**
     * Elimina múltiples peticiones (soft delete)
     *
     * @param  array  $attentionIds  IDs de peticiones a eliminar
     * @param  int|null  $performedBy  ID del usuario que realiza la acción
     * @return array Resultado con contadores de éxito/fallos
     *
     * @throws Exception Si los parámetros son inválidos
     */
    public static function bulkDelete(
        array $attentionIds,
        ?int $performedBy = null
    ): array {
        try {
            if (empty($attentionIds)) {
                throw new Exception('No se proporcionaron IDs de peticiones');
            }

            $success = 0;
            $failed = 0;
            $errors = [];

            DB::beginTransaction();

            try {
                $attentions = Attention::whereIn('id', $attentionIds)->get();

                foreach ($attentions as $attention) {
                    try {
                        $attention->delete();
                        $success++;
                    } catch (Exception $e) {
                        $failed++;
                        $errors[] = [
                            'attention_id' => $attention->id,
                            'radicado' => $attention->radicado,
                            'error' => $e->getMessage(),
                        ];
                        Log::error('Bulk delete failed for attention', [
                            'attention_id' => $attention->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                DB::commit();

                // Log de acción masiva
                self::logBulkAction('delete', $attentionIds, [], $performedBy);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

            return self::buildResult($success, $failed, $errors);
        } catch (Exception $e) {
            Log::error('Bulk delete operation failed', ['error' => $e->getMessage()]);
            throw new Exception('Error en eliminación masiva: '.$e->getMessage());
        }
    }

    /**
     * Cambia el estado de múltiples peticiones
     *
     * @param  array  $attentionIds  IDs de peticiones a actualizar
     * @param  AttentionStatus  $newStatus  Nuevo estado
     * @param  int|null  $performedBy  ID del usuario que realiza la acción
     * @return array Resultado con contadores de éxito/fallos
     *
     * @throws Exception Si los parámetros son inválidos
     */
    public static function bulkChangeStatus(
        array $attentionIds,
        AttentionStatus $newStatus,
        ?int $performedBy = null
    ): array {
        try {
            if (empty($attentionIds)) {
                throw new Exception('No se proporcionaron IDs de peticiones');
            }

            $success = 0;
            $failed = 0;
            $errors = [];

            DB::beginTransaction();

            try {
                $attentions = Attention::whereIn('id', $attentionIds)->get();

                foreach ($attentions as $attention) {
                    try {
                        $oldStatus = $attention->status;

                        $updateData = [
                            'status' => $newStatus,
                            'updated_at' => now(),
                        ];

                        // Si cambia a resuelto, marcar fecha
                        if ($newStatus === AttentionStatus::RESOLVED && $attention->status !== AttentionStatus::RESOLVED) {
                            $updateData['resolved_at'] = now();
                        }

                        // Si cambia a cerrado, marcar fecha
                        if ($newStatus === AttentionStatus::CLOSED && $attention->status !== AttentionStatus::CLOSED) {
                            $updateData['closed_at'] = now();
                            $updateData['closed_by'] = $performedBy;
                        }

                        $attention->update($updateData);

                        // Disparar evento de cambio de estado
                        event(new AttentionStatusChanged($attention, $oldStatus, $newStatus));

                        $success++;
                    } catch (Exception $e) {
                        $failed++;
                        $errors[] = [
                            'attention_id' => $attention->id,
                            'radicado' => $attention->radicado,
                            'error' => $e->getMessage(),
                        ];
                        Log::error('Bulk status change failed for attention', [
                            'attention_id' => $attention->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                DB::commit();

                // Log de acción masiva
                self::logBulkAction('change_status', $attentionIds, [
                    'new_status' => $newStatus->value,
                ], $performedBy);

                // Enviar notificaciones
                if ($success > 0) {
                    self::sendBulkNotifications($attentions->whereIn('id', $attentionIds), 'status_change');
                }
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

            return self::buildResult($success, $failed, $errors);
        } catch (Exception $e) {
            Log::error('Bulk status change operation failed', ['error' => $e->getMessage()]);
            throw new Exception('Error en cambio de estado masivo: '.$e->getMessage());
        }
    }

    /**
     * Registra log de acción masiva
     *
     * @param  string  $action  Tipo de acción realizada
     * @param  array  $attentionIds  IDs afectados
     * @param  array  $details  Detalles adicionales
     * @param  int|null  $performedBy  ID del usuario que realizó la acción
     */
    protected static function logBulkAction(
        string $action,
        array $attentionIds,
        array $details,
        ?int $performedBy
    ): void {
        try {
            Log::info('Bulk action performed', [
                'action' => $action,
                'attention_ids' => $attentionIds,
                'total_affected' => count($attentionIds),
                'details' => $details,
                'performed_by' => $performedBy,
                'timestamp' => now()->toDateTimeString(),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log bulk action', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Envía notificaciones masivas
     *
     * @param  Collection  $attentions  Colección de peticiones afectadas
     * @param  string  $action  Tipo de acción realizada
     */
    protected static function sendBulkNotifications(Collection $attentions, string $action): void
    {
        try {
            // TODO: Implementar notificaciones según configuración del sistema
            // Puede incluir emails, notificaciones in-app, webhooks, etc.

            Log::info('Bulk notifications queued', [
                'action' => $action,
                'count' => $attentions->count(),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send bulk notifications', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Construye resultado estandarizado de operación masiva
     *
     * @param  int  $success  Cantidad de operaciones exitosas
     * @param  int  $failed  Cantidad de operaciones fallidas
     * @param  array  $errors  Detalles de errores
     * @return array Resultado estructurado
     */
    protected static function buildResult(int $success, int $failed, array $errors = []): array
    {
        return [
            'success' => $success,
            'failed' => $failed,
            'total' => $success + $failed,
            'errors' => $errors,
            'success_rate' => ($success + $failed) > 0
                ? round(($success / ($success + $failed)) * 100, 2)
                : 0,
        ];
    }
}
