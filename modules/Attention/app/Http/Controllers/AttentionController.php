<?php

namespace Modules\Attention\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Enums\ResponseType;
use Modules\Attention\Http\Requests\ResolveAttentionRequest;
use Modules\Attention\Http\Requests\SubmitAttentionRequest;
use Modules\Attention\Http\Requests\UpdateAttentionRequest;
use Modules\Attention\Models\Attention;
use Modules\Attention\Services\AttentionNotificationService;
use Throwable;

/**
 * Main controller for PQRSF management
 * Handles both public (citizen) and settings operations
 */
class AttentionController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(
        protected ?AttentionNotificationService $notificationService = null
    ) {
        // Public endpoints don't require authentication
        $this->middleware('auth:sanctum')->except(['submit', 'track']);
    }

    // =========================================================================
    // PUBLIC ENDPOINTS - No authentication required
    // =========================================================================

    /**
     * Submit a new PQRSF (public endpoint)
     * POST /api/pqrsf
     */
    public function submit(SubmitAttentionRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Create the attention record
            $attention = Attention::create([
                'radicado' => Attention::generateRadicado(),
                'type_id' => $request->type_id,
                'category_id' => $request->category_id,
                'sede_id' => $request->sede_id,
                'subject' => $request->subject,
                'description' => $request->description,
                'is_anonymous' => $request->boolean('is_anonymous', false),
                'customer_firstname' => $request->customer_firstname,
                'customer_lastname' => $request->customer_lastname,
                'customer_email' => $request->customer_email,
                'customer_cellphone' => $request->customer_cellphone,
                'customer_dni' => $request->customer_dni,
                'customer_address' => $request->customer_address,
                'response_type' => $request->response_type ? ResponseType::from($request->response_type) : ResponseType::EMAIL,
                'status' => AttentionStatus::RECEIVED,
            ]);

            // Handle file attachments if provided
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $attention->addMedia($file)
                        ->toMediaCollection('attachments');
                }
            }

            // Log initial action
            $attention->logAction(
                'created',
                'PQRSF creado por el ciudadano',
                null // No user ID for public submissions
            );

            // Send confirmation notification to citizen
            if (! $attention->is_anonymous && $attention->customer_email && $this->notificationService) {
                try {
                    $this->notificationService->sendConfirmation($attention);
                } catch (Throwable $e) {
                    Log::error('Failed to send confirmation email', [
                        'radicado' => $attention->radicado,
                        'error' => $e->getMessage(),
                    ]);
                    // Don't fail the request if email fails
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Su solicitud ha sido radicada exitosamente',
                'data' => [
                    'radicado' => $attention->radicado,
                    'status' => $attention->status->value,
                    'status_label' => $attention->status->label(),
                    'created_at' => $attention->created_at->toIso8601String(),
                    'tracking_url' => route('api.pqrsf.track', ['radicado' => $attention->radicado]),
                ],
            ], 201);

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Error creating attention', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al procesar su solicitud. Por favor intente nuevamente.',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Track a PQRSF by radicado number (public endpoint)
     * GET /api/pqrsf/{radicado}
     */
    public function track(string $radicado): JsonResponse
    {
        try {
            $attention = Attention::with(['type', 'category', 'sede'])
                ->byRadicado($radicado)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => [
                    'radicado' => $attention->radicado,
                    'type' => $attention->type->name ?? null,
                    'category' => $attention->category->name ?? null,
                    'subject' => $attention->subject,
                    'status' => $attention->status->value,
                    'status_label' => $attention->status->label(),
                    'status_color' => $attention->status->color(),
                    'created_at' => $attention->created_at->toIso8601String(),
                    'updated_at' => $attention->updated_at->toIso8601String(),
                    'resolved_at' => $attention->resolved_at?->toIso8601String(),
                    'closed_at' => $attention->closed_at?->toIso8601String(),
                    // Public info only - no internal notes or assignments
                    'has_resolution' => ! empty($attention->resolution),
                    'resolution' => $attention->isResolved() || $attention->isClosed() ? $attention->resolution : null,
                ],
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró ninguna solicitud con el radicado proporcionado',
            ], 404);
        }
    }

    // =========================================================================
    // ADMIN ENDPOINTS - Authentication required
    // =========================================================================

    /**
     * List all PQRSF with pagination, filters and search
     * GET /api/settings/pqrsf
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

            // Apply filters
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

            // Date range filter
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Search
            if ($request->filled('search')) {
                $query->search($request->search);
            }

            // Pagination
            $perPage = min($request->integer('per_page', 15), 100); // Max 100 per page
            $attentions = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $attentions->items(),
                'meta' => [
                    'current_page' => $attentions->currentPage(),
                    'last_page' => $attentions->lastPage(),
                    'per_page' => $attentions->perPage(),
                    'total' => $attentions->total(),
                    'from' => $attentions->firstItem(),
                    'to' => $attentions->lastItem(),
                ],
            ]);

        } catch (Throwable $e) {
            Log::error('Error fetching attentions', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las solicitudes',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Show detailed information of a specific PQRSF
     * GET /api/settings/pqrsf/{radicado}
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

            // Get attachments
            $attachments = $attention->getMedia('attachments')->map(function ($media) {
                return [
                    'id' => $media->id,
                    'name' => $media->file_name,
                    'size' => $media->size,
                    'mime_type' => $media->mime_type,
                    'url' => $media->getUrl(),
                    'created_at' => $media->created_at->toIso8601String(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    // Basic info
                    'id' => $attention->id,
                    'uid' => $attention->uid,
                    'radicado' => $attention->radicado,
                    'status' => $attention->status->value,
                    'status_label' => $attention->status->label(),
                    'status_color' => $attention->status->color(),

                    // Type and categorization
                    'type' => $attention->type,
                    'category' => $attention->category,
                    'sede' => $attention->sede,

                    // Customer info
                    'is_anonymous' => $attention->is_anonymous,
                    'customer_firstname' => $attention->customer_firstname,
                    'customer_lastname' => $attention->customer_lastname,
                    'full_name' => $attention->full_name,
                    'customer_email' => $attention->customer_email,
                    'customer_cellphone' => $attention->customer_cellphone,
                    'customer_dni' => $attention->customer_dni,
                    'customer_address' => $attention->customer_address,

                    // Content
                    'subject' => $attention->subject,
                    'description' => $attention->description,

                    // Assignment
                    'department' => $attention->department,
                    'assigned_user' => $attention->assignedUser,

                    // Resolution
                    'response_type' => $attention->response_type?->value,
                    'response_type_label' => $attention->response_type?->label(),
                    'resolution' => $attention->resolution,
                    'resolved_at' => $attention->resolved_at?->toIso8601String(),
                    'closed_at' => $attention->closed_at?->toIso8601String(),

                    // Satisfaction
                    'satisfaction_rating' => $attention->satisfaction_rating,

                    // Timestamps
                    'created_at' => $attention->created_at->toIso8601String(),
                    'updated_at' => $attention->updated_at->toIso8601String(),

                    // Relations
                    'attachments' => $attachments,
                    'notes' => $attention->notes,
                    'actions' => $attention->actions->take(50), // Limit to last 50 actions
                    'mails' => $attention->mails,
                    'satisfaction_surveys' => $attention->satisfactionSurveys,

                    // Permissions
                    'can_be_edited' => $attention->canBeEdited(),
                    'is_resolved' => $attention->isResolved(),
                    'is_closed' => $attention->isClosed(),
                ],
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró la solicitud',
            ], 404);
        }
    }

    /**
     * Update PQRSF basic information
     * PATCH /api/settings/pqrsf/{radicado}
     */
    public function update(UpdateAttentionRequest $request, string $radicado): JsonResponse
    {
        try {
            DB::beginTransaction();

            $attention = Attention::byRadicado($radicado)->firstOrFail();

            // Check if can be edited
            if (! $attention->canBeEdited()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede editar una solicitud en estado '.$attention->status->label(),
                ], 422);
            }

            // Track changes for logging
            $changes = [];
            $updateData = $request->validated();

            foreach ($updateData as $key => $value) {
                if ($value != $attention->$key) {
                    $changes[$key] = [
                        'old' => $attention->$key,
                        'new' => $value,
                    ];
                }
            }

            // Update the record
            $attention->update($updateData);

            // Log the changes
            if (! empty($changes)) {
                $changeDescription = 'Información actualizada: '.implode(', ', array_keys($changes));
                $attention->logAction('updated', $changeDescription, auth()->id());
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
     * Assign PQRSF to a department
     * POST /api/settings/pqrsf/{radicado}/assign-department
     */
    public function assignDepartment(Request $request, string $radicado): JsonResponse
    {
        $request->validate([
            'department_id' => 'required|integer|exists:departments,id',
            'comment' => 'nullable|string|max:500',
        ]);

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

            // Update status to in_process if still received
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
     * Assign PQRSF to a user
     * POST /api/settings/pqrsf/{radicado}/assign-user
     */
    public function assignUser(Request $request, string $radicado): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'comment' => 'nullable|string|max:500',
        ]);

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

            // Update status to in_process if still received
            if ($attention->status === AttentionStatus::RECEIVED) {
                $attention->changeStatus(AttentionStatus::IN_PROCESS, 'Asignado a usuario');
            }

            // Send notification to assigned user
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
     * Change PQRSF status
     * POST /api/settings/pqrsf/{radicado}/change-status
     */
    public function changeStatus(Request $request, string $radicado): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:received,in_process,resolved,closed',
            'comment' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $attention = Attention::byRadicado($radicado)->firstOrFail();
            $newStatus = AttentionStatus::from($request->status);

            // Validation: Can't change to resolved without resolution
            if ($newStatus === AttentionStatus::RESOLVED && empty($attention->resolution)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede cambiar a "Resuelto" sin una respuesta. Use el endpoint de resolución.',
                ], 422);
            }

            // Validation: Can't reopen closed
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
     * Resolve PQRSF with response
     * POST /api/settings/pqrsf/{radicado}/resolve
     */
    public function resolve(ResolveAttentionRequest $request, string $radicado): JsonResponse
    {
        try {
            DB::beginTransaction();

            $attention = Attention::byRadicado($radicado)->firstOrFail();

            // Validation
            if ($attention->isResolved() || $attention->isClosed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La solicitud ya fue resuelta o cerrada',
                ], 422);
            }

            // Resolve the attention
            $responseType = ResponseType::from($request->response_type);
            $attention->resolve($request->resolution, $responseType);

            // Handle resolution attachments if provided
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $attention->addMedia($file)
                        ->toMediaCollection('resolutions');
                }
            }

            // Send notification to citizen
            if ($request->boolean('send_notification', true) && ! $attention->is_anonymous && $attention->customer_email) {
                if ($this->notificationService) {
                    try {
                        $this->notificationService->sendResolution($attention);
                    } catch (Throwable $e) {
                        Log::error('Failed to send resolution notification', [
                            'radicado' => $attention->radicado,
                            'error' => $e->getMessage(),
                        ]);
                    }
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
     * Close PQRSF
     * POST /api/settings/pqrsf/{radicado}/close
     */
    public function close(Request $request, string $radicado): JsonResponse
    {
        $request->validate([
            'comment' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $attention = Attention::byRadicado($radicado)->firstOrFail();

            // Validation
            if ($attention->isClosed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La solicitud ya está cerrada',
                ], 422);
            }

            // Ideally should be resolved before closing
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
     * Add internal note to PQRSF
     * POST /api/settings/pqrsf/{radicado}/notes
     */
    public function addNote(Request $request, string $radicado): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|min:10|max:2000',
        ]);

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
     * Get all notes for a PQRSF
     * GET /api/settings/pqrsf/{radicado}/notes
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
     * Get action history for a PQRSF
     * GET /api/settings/pqrsf/{radicado}/actions
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
     * Get sent emails for a PQRSF
     * GET /api/settings/pqrsf/{radicado}/emails
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

    /**
     * Get statistics
     * GET /api/settings/pqrsf/stats
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            // Date range filter
            $dateFrom = $request->input('date_from', now()->startOfMonth());
            $dateTo = $request->input('date_to', now()->endOfMonth());

            // Base query with date filter
            $query = Attention::query()
                ->whereBetween('created_at', [$dateFrom, $dateTo]);

            // Total counts
            $stats = [
                'total' => $query->count(),
                'by_status' => [
                    'received' => (clone $query)->where('status', AttentionStatus::RECEIVED)->count(),
                    'in_process' => (clone $query)->where('status', AttentionStatus::IN_PROCESS)->count(),
                    'resolved' => (clone $query)->where('status', AttentionStatus::RESOLVED)->count(),
                    'closed' => (clone $query)->where('status', AttentionStatus::CLOSED)->count(),
                ],
                'by_type' => $query->with('type')
                    ->get()
                    ->groupBy('type_id')
                    ->map(fn ($group) => [
                        'name' => $group->first()->type->name ?? 'Sin tipo',
                        'count' => $group->count(),
                    ])
                    ->values(),
                'by_category' => $query->with('category')
                    ->get()
                    ->groupBy('category_id')
                    ->map(fn ($group) => [
                        'name' => $group->first()->category->name ?? 'Sin categoría',
                        'count' => $group->count(),
                    ])
                    ->values(),
                'anonymous' => (clone $query)->where('is_anonymous', true)->count(),
                'average_satisfaction' => (clone $query)->whereNotNull('satisfaction_rating')->avg('satisfaction_rating'),
                'date_range' => [
                    'from' => $dateFrom,
                    'to' => $dateTo,
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (Throwable $e) {
            Log::error('Error fetching stats', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Submit satisfaction survey
     * POST /api/pqrsf/{radicado}/satisfaction
     */
    public function submitSatisfaction(Request $request, string $radicado): JsonResponse
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $attention = Attention::byRadicado($radicado)->firstOrFail();

            // Validation: Must be resolved or closed
            if (! $attention->isResolved() && ! $attention->isClosed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo puede calificar solicitudes resueltas o cerradas',
                ], 422);
            }

            // Update satisfaction rating on main record
            $attention->update([
                'satisfaction_rating' => $request->rating,
            ]);

            // Create satisfaction survey record
            $survey = $attention->satisfactionSurveys()->create([
                'rating' => $request->rating,
                'comment' => $request->comment,
                'submitted_at' => now(),
            ]);

            $attention->logAction(
                'satisfaction_submitted',
                "Calificación de satisfacción: {$request->rating}/5",
                null
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Gracias por su calificación',
                'data' => $survey,
            ], 201);

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Error submitting satisfaction', [
                'radicado' => $radicado,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la calificación',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    // =========================================================================
    // STATISTICS & ANALYTICS ENDPOINTS
    // =========================================================================

    /**
     * Get comprehensive dashboard statistics
     * GET /api/attentions/stats/dashboard
     */
    public function dashboardStats(Request $request): JsonResponse
    {
        try {
            $now = now();

            // Base query
            $baseQuery = Attention::query();

            // Total PQRSF statistics
            $totalAll = (clone $baseQuery)->count();
            $totalToday = (clone $baseQuery)->whereDate('created_at', $now->toDateString())->count();
            $totalWeek = (clone $baseQuery)->whereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])->count();
            $totalMonth = (clone $baseQuery)->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count();

            // Statistics by status
            $byStatus = [
                'received' => (clone $baseQuery)->where('status', AttentionStatus::RECEIVED)->count(),
                'in_process' => (clone $baseQuery)->where('status', AttentionStatus::IN_PROCESS)->count(),
                'resolved' => (clone $baseQuery)->where('status', AttentionStatus::RESOLVED)->count(),
                'closed' => (clone $baseQuery)->where('status', AttentionStatus::CLOSED)->count(),
            ];

            // Statistics by type
            $byType = Attention::select('type_id', DB::raw('count(*) as count'))
                ->with('type:id,name,code')
                ->groupBy('type_id')
                ->get()
                ->map(fn ($item) => [
                    'type' => $item->type?->name ?? 'Sin tipo',
                    'code' => $item->type?->code ?? null,
                    'count' => $item->count,
                ]);

            // Average satisfaction rating
            $avgSatisfaction = Attention::whereNotNull('satisfaction_rating')->avg('satisfaction_rating');

            // Average resolution time (in hours)
            $avgResolutionTime = Attention::whereNotNull('resolved_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
                ->value('avg_hours');

            // SLA compliance rate
            $totalWithSla = Attention::whereNotNull('sla_policy_id')->count();
            $breachedCount = Attention::whereHas('breaches')->distinct('id')->count();
            $slaComplianceRate = $totalWithSla > 0 ? (($totalWithSla - $breachedCount) / $totalWithSla) * 100 : null;

            // Top 5 categories
            $topCategories = Attention::select('category_id', DB::raw('count(*) as count'))
                ->with('category:id,name')
                ->groupBy('category_id')
                ->orderByDesc('count')
                ->limit(5)
                ->get()
                ->map(fn ($item) => [
                    'category' => $item->category?->name ?? 'Sin categoría',
                    'count' => $item->count,
                ]);

            // Top 5 departments with most workload
            $topDepartments = Attention::select('department_id', DB::raw('count(*) as count'))
                ->with('department:id,name')
                ->whereNotNull('department_id')
                ->whereIn('status', [AttentionStatus::RECEIVED, AttentionStatus::IN_PROCESS])
                ->groupBy('department_id')
                ->orderByDesc('count')
                ->limit(5)
                ->get()
                ->map(fn ($item) => [
                    'department' => $item->department?->name ?? 'Sin departamento',
                    'count' => $item->count,
                ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'totals' => [
                        'all' => $totalAll,
                        'today' => $totalToday,
                        'week' => $totalWeek,
                        'month' => $totalMonth,
                    ],
                    'by_status' => $byStatus,
                    'by_type' => $byType,
                    'satisfaction' => [
                        'average' => $avgSatisfaction ? round($avgSatisfaction, 2) : null,
                        'total_rated' => Attention::whereNotNull('satisfaction_rating')->count(),
                    ],
                    'performance' => [
                        'avg_resolution_time_hours' => $avgResolutionTime ? round($avgResolutionTime, 2) : null,
                        'sla_compliance_rate' => $slaComplianceRate ? round($slaComplianceRate, 2) : null,
                        'total_with_sla' => $totalWithSla,
                        'breached_count' => $breachedCount,
                    ],
                    'top_categories' => $topCategories,
                    'top_departments' => $topDepartments,
                    'generated_at' => $now->toIso8601String(),
                ],
            ]);

        } catch (Throwable $e) {
            Log::error('Error fetching dashboard stats', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas del dashboard',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get statistics grouped by attention type
     * GET /api/attentions/stats/by-type
     */
    public function statsByType(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        try {
            $query = Attention::query();

            // Apply date filters
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Group by type and calculate metrics
            $stats = $query->with('type:id,name,code')
                ->get()
                ->groupBy('type_id')
                ->map(function ($group) {
                    $type = $group->first()->type;

                    // Calculate average resolution time for resolved items
                    $resolvedItems = $group->filter(fn ($item) => $item->resolved_at !== null);
                    $avgResolutionMinutes = $resolvedItems->isNotEmpty()
                        ? $resolvedItems->avg(fn ($item) => $item->created_at->diffInMinutes($item->resolved_at))
                        : null;

                    // Calculate average satisfaction
                    $ratedItems = $group->filter(fn ($item) => $item->satisfaction_rating !== null);
                    $avgSatisfaction = $ratedItems->isNotEmpty() ? $ratedItems->avg('satisfaction_rating') : null;

                    return [
                        'type_id' => $type?->id,
                        'type_name' => $type?->name ?? 'Sin tipo',
                        'type_code' => $type?->code ?? null,
                        'count' => $group->count(),
                        'avg_resolution_time_hours' => $avgResolutionMinutes ? round($avgResolutionMinutes / 60, 2) : null,
                        'avg_satisfaction' => $avgSatisfaction ? round($avgSatisfaction, 2) : null,
                        'total_resolved' => $resolvedItems->count(),
                        'total_rated' => $ratedItems->count(),
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'filters' => [
                    'date_from' => $request->date_from,
                    'date_to' => $request->date_to,
                ],
            ]);

        } catch (Throwable $e) {
            Log::error('Error fetching stats by type', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas por tipo',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get statistics grouped by status
     * GET /api/attentions/stats/by-status
     */
    public function statsByStatus(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        try {
            $query = Attention::query();

            // Apply date filters
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Get all attentions
            $attentions = $query->get();

            // Group by status and calculate metrics
            $stats = collect(AttentionStatus::cases())
                ->map(function ($status) use ($attentions) {
                    $group = $attentions->filter(fn ($item) => $item->status === $status);

                    // Calculate average time in current status
                    $avgTimeInStatus = $group->isNotEmpty()
                        ? $group->avg(fn ($item) => $item->updated_at->diffInHours($item->created_at))
                        : null;

                    return [
                        'status' => $status->value,
                        'status_label' => $status->label(),
                        'status_color' => $status->color(),
                        'count' => $group->count(),
                        'avg_time_in_status_hours' => $avgTimeInStatus ? round($avgTimeInStatus, 2) : null,
                        'percentage' => $attentions->count() > 0 ? round(($group->count() / $attentions->count()) * 100, 2) : 0,
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'total' => $attentions->count(),
                'filters' => [
                    'date_from' => $request->date_from,
                    'date_to' => $request->date_to,
                ],
            ]);

        } catch (Throwable $e) {
            Log::error('Error fetching stats by status', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas por estado',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    // =========================================================================
    // SLA MONITORING ENDPOINTS
    // =========================================================================

    /**
     * Get SLA status for a specific PQRSF
     * GET /api/attentions/{radicado}/sla-status
     */
    public function slaStatus(string $radicado): JsonResponse
    {
        try {
            $attention = Attention::byRadicado($radicado)
                ->with(['slaPolicy', 'breaches'])
                ->firstOrFail();

            if (! $attention->slaPolicy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta atención no tiene una política SLA asignada',
                ], 404);
            }

            $policy = $attention->slaPolicy;
            $now = now();

            // Calculate elapsed time
            $createdAt = $attention->created_at;
            $elapsedMinutes = $createdAt->diffInMinutes($now);

            // Get resolution time limit (in minutes)
            $resolutionTimeLimit = $policy->resolution_time;

            // Calculate percentage used
            $percentageUsed = $resolutionTimeLimit > 0 ? ($elapsedMinutes / $resolutionTimeLimit) * 100 : 0;

            // Determine SLA status
            $slaStatus = 'on_time';
            if ($percentageUsed >= 100) {
                $slaStatus = 'breached';
            } elseif ($percentageUsed >= 80) {
                $slaStatus = 'warning';
            }

            // Check for existing breaches
            $hasBreaches = $attention->breaches()->exists();

            // Calculate next milestone
            $nextMilestone = null;
            if ($percentageUsed < 80) {
                $nextMilestone = [
                    'type' => 'warning',
                    'threshold_percent' => 80,
                    'minutes_remaining' => max(0, ($resolutionTimeLimit * 0.8) - $elapsedMinutes),
                ];
            } elseif ($percentageUsed < 100) {
                $nextMilestone = [
                    'type' => 'breach',
                    'threshold_percent' => 100,
                    'minutes_remaining' => max(0, $resolutionTimeLimit - $elapsedMinutes),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'attention_uid' => $attention->uid,
                    'radicado' => $attention->radicado,
                    'status' => $attention->status->value,
                    'sla_policy' => [
                        'id' => $policy->id,
                        'name' => $policy->name,
                        'resolution_time_minutes' => $policy->resolution_time,
                    ],
                    'time_tracking' => [
                        'created_at' => $createdAt->toIso8601String(),
                        'elapsed_minutes' => round($elapsedMinutes, 2),
                        'elapsed_hours' => round($elapsedMinutes / 60, 2),
                        'limit_minutes' => $resolutionTimeLimit,
                        'limit_hours' => round($resolutionTimeLimit / 60, 2),
                        'percentage_used' => round($percentageUsed, 2),
                    ],
                    'sla_status' => $slaStatus,
                    'has_breaches' => $hasBreaches,
                    'breach_count' => $attention->breaches()->count(),
                    'next_milestone' => $nextMilestone,
                    'checked_at' => $now->toIso8601String(),
                ],
            ]);

        } catch (Throwable $e) {
            Log::error('Error fetching SLA status', [
                'radicado' => $radicado,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el estado SLA',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 404);
        }
    }

    /**
     * Get list of PQRSF with SLA breaches
     * GET /api/attentions/sla-breaches
     */
    public function slaBreaches(Request $request): JsonResponse
    {
        $request->validate([
            'breach_type' => 'nullable|string|in:response,resolution,closure',
            'department_id' => 'nullable|integer|exists:attention_departments,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $query = Attention::whereHas('breaches')
                ->with(['type', 'category', 'department', 'assignedUser', 'slaPolicy', 'breaches']);

            // Apply filters
            if ($request->filled('department_id')) {
                $query->where('department_id', $request->department_id);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            if ($request->filled('breach_type')) {
                $query->whereHas('breaches', function ($q) use ($request) {
                    $q->where('breach_type', $request->breach_type);
                });
            }

            // Calculate severity and order by it
            $attentions = $query->get()->map(function ($attention) {
                $elapsedMinutes = $attention->created_at->diffInMinutes(now());
                $limitMinutes = $attention->slaPolicy?->resolution_time ?? 0;
                $overageMinutes = $limitMinutes > 0 ? max(0, $elapsedMinutes - $limitMinutes) : 0;
                $overagePercent = $limitMinutes > 0 ? ($overageMinutes / $limitMinutes) * 100 : 0;

                $attention->severity_score = $overagePercent;

                return $attention;
            })->sortByDesc('severity_score');

            // Paginate results
            $perPage = min($request->integer('per_page', 15), 100);
            $page = $request->integer('page', 1);
            $offset = ($page - 1) * $perPage;

            $paginatedAttentions = $attentions->slice($offset, $perPage)->values();
            $total = $attentions->count();

            return response()->json([
                'success' => true,
                'data' => $paginatedAttentions,
                'meta' => [
                    'current_page' => $page,
                    'last_page' => (int) ceil($total / $perPage),
                    'per_page' => $perPage,
                    'total' => $total,
                    'from' => $offset + 1,
                    'to' => min($offset + $perPage, $total),
                ],
                'filters' => [
                    'breach_type' => $request->breach_type,
                    'department_id' => $request->department_id,
                    'date_from' => $request->date_from,
                    'date_to' => $request->date_to,
                ],
            ]);

        } catch (Throwable $e) {
            Log::error('Error fetching SLA breaches', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener incumplimientos SLA',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    // =========================================================================
    // BULK ACTIONS ENDPOINTS
    // =========================================================================

    /**
     * Bulk assign PQRSF to department or user
     * POST /api/attentions/bulk-assign
     */
    public function bulkAssign(\Modules\Attention\Http\Requests\BulkActionRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $attentionIds = $request->attention_ids;
            $departmentId = $request->department_id;
            $userId = $request->user_id;

            $successCount = 0;
            $failureCount = 0;
            $errors = [];

            foreach ($attentionIds as $attentionId) {
                try {
                    $attention = Attention::findOrFail($attentionId);

                    // Skip if already closed
                    if ($attention->isClosed()) {
                        $failureCount++;
                        $errors[] = "Radicado {$attention->radicado}: Ya está cerrado";

                        continue;
                    }

                    // Assign to department if provided
                    if ($departmentId) {
                        $attention->assignToDepartment($departmentId, 'Asignación masiva');
                    }

                    // Assign to user if provided
                    if ($userId) {
                        $attention->assignTo($userId, 'Asignación masiva');
                    }

                    // Update status to in_process if still received
                    if ($attention->status === AttentionStatus::RECEIVED) {
                        $attention->changeStatus(AttentionStatus::IN_PROCESS, 'Asignación masiva');
                    }

                    // Send notification to assigned user
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
                    Log::error('Error in bulk assign', [
                        'attention_id' => $attentionId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Log bulk action
            Log::info('Bulk assign completed', [
                'user_id' => auth()->id(),
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'department_id' => $departmentId,
                'user_id' => $userId,
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
     * Bulk close PQRSF
     * POST /api/attentions/bulk-close
     */
    public function bulkClose(\Modules\Attention\Http\Requests\BulkActionRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $attentionIds = $request->attention_ids;
            $successCount = 0;
            $failureCount = 0;
            $errors = [];

            foreach ($attentionIds as $attentionId) {
                try {
                    $attention = Attention::findOrFail($attentionId);

                    // Skip if already closed
                    if ($attention->isClosed()) {
                        $failureCount++;
                        $errors[] = "Radicado {$attention->radicado}: Ya está cerrado";

                        continue;
                    }

                    // Validate that it's resolved
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
                    Log::error('Error in bulk close', [
                        'attention_id' => $attentionId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Log bulk action
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
     * Bulk delete PQRSF (soft delete)
     * DELETE /api/attentions/bulk-delete
     */
    public function bulkDelete(\Modules\Attention\Http\Requests\BulkActionRequest $request): JsonResponse
    {
        try {
            // Only super-settings can bulk delete
            // This should be enforced by middleware/policy
            // Here we just add an extra check
            if (! auth()->user()->hasRole('super-settings')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para realizar eliminación masiva',
                ], 403);
            }

            DB::beginTransaction();

            $attentionIds = $request->attention_ids;
            $successCount = 0;
            $failureCount = 0;
            $errors = [];

            foreach ($attentionIds as $attentionId) {
                try {
                    $attention = Attention::findOrFail($attentionId);

                    // Log before deletion
                    Log::warning('Attention soft deleted via bulk action', [
                        'attention_id' => $attention->id,
                        'radicado' => $attention->radicado,
                        'deleted_by' => auth()->id(),
                    ]);

                    // Soft delete
                    $attention->delete();
                    $successCount++;

                } catch (Throwable $e) {
                    $failureCount++;
                    $errors[] = "Atención ID {$attentionId}: {$e->getMessage()}";
                    Log::error('Error in bulk delete', [
                        'attention_id' => $attentionId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Log bulk action
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

    // =========================================================================
    // EXPORT ENDPOINTS
    // =========================================================================

    /**
     * Request export of PQRSF data
     * POST /api/attentions/export
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|string|in:excel,pdf,csv',
            'filters' => 'nullable|array',
        ]);

        try {
            // Generate unique token for this export
            $token = bin2hex(random_bytes(32));

            // Store export request in cache (valid for 1 hour)
            cache()->put("export_{$token}", [
                'user_id' => auth()->id(),
                'format' => $request->format,
                'filters' => $request->filters ?? [],
                'status' => 'pending',
                'created_at' => now()->toIso8601String(),
            ], now()->addHour());

            // TODO: Dispatch job to generate export
            // dispatch(new GenerateAttentionExportJob($token, $request->format, $request->filters ?? []));

            Log::info('Export requested', [
                'user_id' => auth()->id(),
                'format' => $request->format,
                'token' => $token,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Exportación solicitada. Recibirá una notificación cuando esté lista.',
                'data' => [
                    'token' => $token,
                    'download_url' => route('api.attentions.export.download', ['token' => $token]),
                    'expires_at' => now()->addHour()->toIso8601String(),
                ],
            ], 202);

        } catch (Throwable $e) {
            Log::error('Error requesting export', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al solicitar la exportación',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Download exported file
     * GET /api/attentions/export/{token}
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|JsonResponse
     */
    public function downloadExport(string $token)
    {
        try {
            // Validate token exists in cache
            $exportData = cache()->get("export_{$token}");

            if (! $exportData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de exportación no válido o expirado',
                ], 404);
            }

            // Verify ownership
            if ($exportData['user_id'] !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para descargar esta exportación',
                ], 403);
            }

            // Check if export is ready
            if ($exportData['status'] !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'La exportación aún está en proceso',
                    'data' => [
                        'status' => $exportData['status'],
                    ],
                ], 202);
            }

            // Get file path
            $filePath = storage_path("app/exports/{$token}.{$exportData['format']}");

            if (! file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo de exportación no encontrado',
                ], 404);
            }

            // Determine content type
            $contentType = match ($exportData['format']) {
                'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'pdf' => 'application/pdf',
                'csv' => 'text/csv',
                default => 'application/octet-stream',
            };

            $fileName = 'pqrsf_export_'.now()->format('Y-m-d_His').".{$exportData['format']}";

            Log::info('Export downloaded', [
                'user_id' => auth()->id(),
                'token' => $token,
            ]);

            return response()->stream(
                function () use ($filePath) {
                    $stream = fopen($filePath, 'r');
                    fpassthru($stream);
                    fclose($stream);
                },
                200,
                [
                    'Content-Type' => $contentType,
                    'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
                    'Content-Length' => filesize($filePath),
                ]
            );

        } catch (Throwable $e) {
            Log::error('Error downloading export', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al descargar la exportación',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    // =========================================================================
    // NOTIFICATION ENDPOINTS (Legacy compatibility)
    // =========================================================================

    /**
     * Send confirmation notification to citizen
     * POST /api/attentions/{radicado}/send-confirmation
     *
     * @deprecated This method is deprecated. Confirmations are now sent automatically on creation.
     */
    public function sendConfirmation(string $radicado): JsonResponse
    {
        try {
            $attention = Attention::byRadicado($radicado)->firstOrFail();

            if ($attention->is_anonymous || ! $attention->customer_email) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede enviar confirmación a un usuario anónimo o sin email',
                ], 422);
            }

            if (! $this->notificationService) {
                return response()->json([
                    'success' => false,
                    'message' => 'Servicio de notificaciones no disponible',
                ], 503);
            }

            $this->notificationService->sendConfirmation($attention);

            Log::info('Confirmation email sent manually', [
                'radicado' => $radicado,
                'sent_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Correo de confirmación enviado exitosamente',
            ]);

        } catch (Throwable $e) {
            Log::error('Error sending confirmation', [
                'radicado' => $radicado,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el correo de confirmación',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Send resolution notification to citizen
     * POST /api/attentions/{radicado}/send-resolution
     *
     * @deprecated This method is deprecated. Resolution notifications are now sent automatically when resolving.
     */
    public function sendResolution(string $radicado): JsonResponse
    {
        try {
            $attention = Attention::byRadicado($radicado)->firstOrFail();

            if (! $attention->isResolved() && ! $attention->isClosed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La solicitud debe estar resuelta antes de enviar notificación',
                ], 422);
            }

            if ($attention->is_anonymous || ! $attention->customer_email) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede enviar notificación a un usuario anónimo o sin email',
                ], 422);
            }

            if (! $this->notificationService) {
                return response()->json([
                    'success' => false,
                    'message' => 'Servicio de notificaciones no disponible',
                ], 503);
            }

            $this->notificationService->sendResolution($attention);

            Log::info('Resolution email sent manually', [
                'radicado' => $radicado,
                'sent_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Correo de resolución enviado exitosamente',
            ]);

        } catch (Throwable $e) {
            Log::error('Error sending resolution', [
                'radicado' => $radicado,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el correo de resolución',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }
}
