<?php

namespace Modules\Attention\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Enums\ResponseType;
use Modules\Attention\Http\Requests\AddNoteRequest;
use Modules\Attention\Http\Requests\AssignDepartmentRequest;
use Modules\Attention\Http\Requests\AssignUserRequest;
use Modules\Attention\Http\Requests\ChangeStatusRequest;
use Modules\Attention\Http\Requests\CloseAttentionRequest;
use Modules\Attention\Http\Requests\ResolveAttentionRequest;
use Modules\Attention\Http\Requests\UpdateAttentionRequest;
use Modules\Attention\Http\Resources\AttentionListResource;
use Modules\Attention\Models\Attention;
use Modules\Attention\Services\AttentionNotificationService;
use Throwable;

/**
 * Core admin controller for peticiones management.
 * Handles listing, detail, updates, assignment, status changes, notes and history.
 *
 * Split-off controllers:
 *  - AttentionPublicApiController  — submit, track, submitSatisfaction
 *  - AttentionStatsApiController   — stats, dashboardStats, statsByType, statsByStatus
 *  - AttentionSlaApiController     — slaStatus, slaBreaches
 *  - AttentionBulkApiController    — bulkAssign, bulkClose, bulkDelete
 *  - AttentionExportApiController  — export, downloadExport
 *  - AttentionNotificationApiController — sendConfirmation, sendResolution (deprecated)
 */
class AttentionController extends Controller
{
    public function __construct(
        protected ?AttentionNotificationService $notificationService = null
    ) {}

    // =========================================================================
    // ADMIN ENDPOINTS — Authentication required (enforced via route middleware)
    // =========================================================================

    /**
     * List all peticiones with pagination, filters and search
     * GET /api/attentions
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Attention::with([
                'type',
                'category',
                'sede',
                'department',
                'assignedUser',
            ])->recent();

            if ($request->filled('status')) {
                $query->byStatus(AttentionStatus::from($request->status));
            }

            if ($request->filled('type_id')) {
                $query->where('type_id', $request->type_id);
            }

            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->filled('department_id')) {
                $query->byDepartment($request->department_id);
            }

            if ($request->filled('assigned_user_id')) {
                $query->assignedTo($request->assigned_user_id);
            }

            if ($request->filled('sede_id')) {
                $query->where('sede_id', $request->sede_id);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            $perPage = min($request->integer('per_page', 15), 100);
            $attentions = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => AttentionListResource::collection($attentions->getCollection()),
                'meta' => [
                    'currentPage' => $attentions->currentPage(),
                    'lastPage' => $attentions->lastPage(),
                    'perPage' => $attentions->perPage(),
                    'total' => $attentions->total(),
                    'from' => $attentions->firstItem(),
                    'to' => $attentions->lastItem(),
                ],
            ]);

        } catch (Throwable $e) {
            Log::error('Error fetching attentions', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las solicitudes',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Show detailed information of a specific peticion
     * GET /api/attentions/{radicado}
     */
    public function show(string $radicado): JsonResponse
    {
        try {
            $attention = Attention::with([
                'type',
                'category',
                'sede',
                'department',
                'assignedUser',
                'notes.user',
                'actions.user',
                'mails',
                'satisfactionSurveys',
            ])
                ->byRadicado($radicado)
                ->firstOrFail();

            $attachments = $attention->getMedia('attachments')->map(fn ($media) => [
                'id' => $media->id,
                'name' => $media->file_name,
                'size' => $media->size,
                'mime_type' => $media->mime_type,
                'url' => $media->getUrl(),
                'created_at' => $media->created_at->toIso8601String(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $attention->id,
                    'radicado' => $attention->radicado,
                    'type' => $attention->type,
                    'category' => $attention->category,
                    'sede' => $attention->sede,
                    'department' => $attention->department,
                    'assigned_user' => $attention->assignedUser,
                    'subject' => $attention->subject,
                    'description' => $attention->description,
                    'is_anonymous' => $attention->is_anonymous,
                    'customer_firstname' => $attention->customer_firstname,
                    'customer_lastname' => $attention->customer_lastname,
                    'customer_email' => $attention->customer_email,
                    'customer_cellphone' => $attention->customer_cellphone,
                    'customer_dni' => $attention->customer_dni,
                    'customer_address' => $attention->customer_address,
                    'response_type' => $attention->response_type?->value,
                    'status' => $attention->status->value,
                    'status_label' => $attention->status->label(),
                    'status_color' => $attention->status->color(),
                    'resolution' => $attention->resolution,
                    'satisfaction_rating' => $attention->satisfaction_rating,
                    'notes' => $attention->notes,
                    'actions' => $attention->actions,
                    'mails' => $attention->mails,
                    'attachments' => $attachments,
                    'satisfaction_surveys' => $attention->satisfactionSurveys,
                    'created_at' => $attention->created_at->toIso8601String(),
                    'updated_at' => $attention->updated_at->toIso8601String(),
                    'resolved_at' => $attention->resolved_at?->toIso8601String(),
                    'closed_at' => $attention->closed_at?->toIso8601String(),
                ],
            ]);

        } catch (Throwable $e) {
            Log::error('Error fetching attention detail', [
                'radicado' => $radicado,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se encontró la solicitud',
            ], 404);
        }
    }

    /**
     * Update peticion information
     * PATCH /api/attentions/{radicado}
     */
    public function update(UpdateAttentionRequest $request, string $radicado): JsonResponse
    {
        try {
            DB::beginTransaction();

            $attention = Attention::byRadicado($radicado)->firstOrFail();

            if (! $attention->canBeEdited()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede editar una solicitud en estado '.$attention->status->label(),
                ], 422);
            }

            $updateData = $request->validated();
            $changes = [];

            foreach ($updateData as $key => $value) {
                if ($value != $attention->$key) {
                    $changes[$key] = ['old' => $attention->$key, 'new' => $value];
                }
            }

            $attention->update($updateData);

            if (! empty($changes)) {
                $attention->logAction(
                    'updated',
                    'Información actualizada: '.implode(', ', array_keys($changes)),
                    auth()->id()
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud actualizada exitosamente',
                'data' => $attention->fresh(['type', 'category', 'sede', 'department', 'assignedUser']),
            ]);

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Error updating attention', [
                'radicado' => $radicado,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la solicitud',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Assign peticion to a department
     * POST /api/attentions/{radicado}/assign-department
     */
    public function assignDepartment(AssignDepartmentRequest $request, string $radicado): JsonResponse
    {
        try {
            DB::beginTransaction();

            $attention = Attention::byRadicado($radicado)->firstOrFail();

            if ($attention->isClosed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede reasignar una solicitud cerrada',
                ], 422);
            }

            $attention->assignToDepartment($request->department_id, $request->comment);

            if ($attention->status === AttentionStatus::RECEIVED) {
                $attention->changeStatus(AttentionStatus::IN_PROCESS, 'Asignado a departamento');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud asignada al departamento exitosamente',
                'data' => $attention->fresh(['department', 'assignedUser']),
            ]);

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Error assigning department', [
                'radicado' => $radicado,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al asignar el departamento',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Assign peticion to a user
     * POST /api/attentions/{radicado}/assign-user
     */
    public function assignUser(AssignUserRequest $request, string $radicado): JsonResponse
    {
        try {
            DB::beginTransaction();

            $attention = Attention::byRadicado($radicado)->firstOrFail();

            if ($attention->isClosed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede reasignar una solicitud cerrada',
                ], 422);
            }

            $attention->assignTo($request->user_id, $request->comment);

            if ($attention->status === AttentionStatus::RECEIVED) {
                $attention->changeStatus(AttentionStatus::IN_PROCESS, 'Asignado a usuario');
            }

            if ($this->notificationService) {
                try {
                    $this->notificationService->notifyAssigned($attention);
                } catch (Throwable $e) {
                    Log::error('Failed to send assignment notification', [
                        'radicado' => $attention->radicado,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud asignada al usuario exitosamente',
                'data' => $attention->fresh(['department', 'assignedUser']),
            ]);

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Error assigning user', [
                'radicado' => $radicado,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al asignar el usuario',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Change peticion status
     * POST /api/attentions/{radicado}/change-status
     */
    public function changeStatus(ChangeStatusRequest $request, string $radicado): JsonResponse
    {
        try {
            DB::beginTransaction();

            $attention = Attention::byRadicado($radicado)->firstOrFail();
            $newStatus = AttentionStatus::from($request->status);

            if ($newStatus === AttentionStatus::RESOLVED && empty($attention->resolution)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede cambiar a "Resuelto" sin una respuesta. Use el endpoint de resolución.',
                ], 422);
            }

            if ($attention->isClosed() && $newStatus !== AttentionStatus::CLOSED) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede reabrir una solicitud cerrada',
                ], 422);
            }

            $attention->changeStatus($newStatus, $request->comment);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado exitosamente',
                'data' => [
                    'status' => $attention->status->value,
                    'status_label' => $attention->status->label(),
                ],
            ]);

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Error changing status', [
                'radicado' => $radicado,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Resolve peticion with response
     * POST /api/attentions/{radicado}/resolve
     */
    public function resolve(ResolveAttentionRequest $request, string $radicado): JsonResponse
    {
        try {
            DB::beginTransaction();

            $attention = Attention::byRadicado($radicado)->firstOrFail();

            if ($attention->isResolved() || $attention->isClosed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La solicitud ya fue resuelta o cerrada',
                ], 422);
            }

            $responseType = ResponseType::from($request->response_type);
            $attention->resolve($request->resolution, $responseType);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $attention->addMedia($file)->toMediaCollection('resolutions');
                }
            }

            if ($request->boolean('send_notification', true) && ! $attention->is_anonymous && $attention->customer_email && $this->notificationService) {
                try {
                    $this->notificationService->sendResolution($attention);
                } catch (Throwable $e) {
                    Log::error('Failed to send resolution notification', [
                        'radicado' => $attention->radicado,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud resuelta exitosamente',
                'data' => $attention->fresh(['type', 'category']),
            ]);

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Error resolving attention', [
                'radicado' => $radicado,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al resolver la solicitud',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Close peticion
     * POST /api/attentions/{radicado}/close
     */
    public function close(CloseAttentionRequest $request, string $radicado): JsonResponse
    {
        try {
            DB::beginTransaction();

            $attention = Attention::byRadicado($radicado)->firstOrFail();

            if ($attention->isClosed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La solicitud ya está cerrada',
                ], 422);
            }

            if (! $attention->isResolved()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Se recomienda resolver la solicitud antes de cerrarla',
                ], 422);
            }

            $attention->close($request->comment);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud cerrada exitosamente',
                'data' => [
                    'status' => $attention->status->value,
                    'closed_at' => $attention->closed_at->toIso8601String(),
                ],
            ]);

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Error closing attention', [
                'radicado' => $radicado,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar la solicitud',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Add internal note to peticion
     * POST /api/attentions/{radicado}/notes
     */
    public function addNote(AddNoteRequest $request, string $radicado): JsonResponse
    {
        try {
            DB::beginTransaction();

            $attention = Attention::byRadicado($radicado)->firstOrFail();
            $note = $attention->addNote($request->content, auth()->id());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Nota agregada exitosamente',
                'data' => $note->load('user'),
            ], 201);

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Error adding note', [
                'radicado' => $radicado,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al agregar la nota',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get all notes for a peticion
     * GET /api/attentions/{radicado}/notes
     */
    public function getNotes(string $radicado): JsonResponse
    {
        try {
            $attention = Attention::byRadicado($radicado)->firstOrFail();

            $notes = $attention->notes()
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $notes,
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró la solicitud',
            ], 404);
        }
    }

    /**
     * Get action history for a peticion
     * GET /api/attentions/{radicado}/actions
     */
    public function getActions(string $radicado): JsonResponse
    {
        try {
            $attention = Attention::byRadicado($radicado)->firstOrFail();

            $actions = $attention->actions()
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $actions,
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró la solicitud',
            ], 404);
        }
    }

    /**
     * Get sent emails for a peticion
     * GET /api/attentions/{radicado}/emails
     */
    public function getEmails(string $radicado): JsonResponse
    {
        try {
            $attention = Attention::byRadicado($radicado)->firstOrFail();

            $emails = $attention->mails()
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $emails,
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró la solicitud',
            ], 404);
        }
    }
}
