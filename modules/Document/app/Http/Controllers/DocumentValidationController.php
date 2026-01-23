<?php

namespace Modules\Document\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentValidatorGroup;
use Modules\Document\Services\DocumentActionService;

/**
 * Handles document validation workflow operations
 * Extracted from DocumentsController for better separation of concerns
 */
class DocumentValidationController extends Controller
{
    /**
     * Approve current validation stage
     */
    public function approveStage(Request $request, $uid): JsonResponse
    {
        try {
            $document = Document::findByUid($uid);

            if (! $document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Documento no encontrado.',
                ], 404);
            }

            // Validar que el documento está en estado de validación
            if (! in_array($document->validation_status, ['pending', 'in_validation'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'El documento no está en proceso de validación.',
                ], 400);
            }

            $comments = $request->input('comments');
            $assignedUserId = $request->input('assigned_user_id'); // Puede ser null

            // Validar que el usuario asignado existe y pertenece al grupo correcto (si se especifica)
            if ($assignedUserId) {
                $user = \App\Models\User::find($assignedUserId);
                if (! $user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Usuario asignado no encontrado.',
                    ], 400);
                }
            }

            // Obtener usuario autenticado
            $validator = auth()->user();
            if (! $validator) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado.',
                ], 401);
            }

            // Aprobar etapa actual
            $result = $document->approveCurrentStage($validator, $comments, $assignedUserId);

            if (! $result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al aprobar la etapa.',
                ], 500);
            }

            // Preparar respuesta
            $response = [
                'success' => true,
                'message' => 'Etapa aprobada correctamente',
                'document' => [
                    'validation_status' => $document->validation_status,
                    'current_stage' => $document->current_stage,
                    'total_stages' => $document->total_stages,
                    'current_validator_group' => $document->current_validator_group,
                    'assigned_user_id' => $document->assigned_user_id,
                ],
            ];

            // Si hay siguiente etapa, incluir información
            if ($document->validation_status === 'in_validation') {
                $response['next_group'] = $document->current_validator_group;
                $response['assigned_to'] = $document->assigned_user_id
                    ? \App\Models\User::find($document->assigned_user_id)->full_name
                    : 'Todo el grupo';
            }

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Error approving stage', [
                'uid' => $uid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar etapa: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject document at current stage
     */
    public function rejectStage(Request $request, $uid): JsonResponse
    {
        try {
            $document = Document::findByUid($uid);

            if (! $document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Documento no encontrado.',
                ], 404);
            }

            // Validar que el documento está en estado de validación
            if (! in_array($document->validation_status, ['pending', 'in_validation'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'El documento no está en proceso de validación.',
                ], 400);
            }

            $request->validate([
                'reason' => 'required|string|min:10',
            ]);

            $reason = $request->input('reason');

            // Obtener usuario autenticado
            $validator = auth()->user();
            if (! $validator) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado.',
                ], 401);
            }

            // Rechazar documento
            $result = $document->rejectDocument($validator, $reason);

            if (! $result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al rechazar el documento.',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Documento rechazado correctamente',
                'document' => [
                    'validation_status' => $document->validation_status,
                    'validation_completed_at' => $document->validation_completed_at,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error rejecting stage', [
                'uid' => $uid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar documento: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirm document upload
     */
    public function confirmDocumentUpload($uid): JsonResponse
    {
        $document = Document::findByUid($uid);

        if (! $document) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Document not found.',
            ], 404);
        }

        if (! $document->confirmed_at || $document->media->count() === 0) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Document has not been uploaded yet.',
            ], 400);
        }

        $document->confirmed_at = now();
        $document->proccess = 1;
        $document->save();

        // Registrar la acción
        DocumentActionService::logUploadConfirmation($document);

        return response()->json([
            'success' => true,
            'message' => 'Carga de documento confirmada correctamente',
        ]);
    }

    /**
     * Get validator groups for a user
     * Helper method for filtering documents by validator group
     */
    public function getUserValidatorGroups($user = null): array
    {
        if (! $user) {
            return [];
        }

        // Obtener todos los grupos de validación que el usuario pertenece
        $validatorGroups = DocumentValidatorGroup::whereHas(
            'users',
            fn ($q) => $q->where('users.id', $user->id)
        )->pluck('key')->toArray();

        return $validatorGroups ?: [];
    }
}
