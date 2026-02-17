<?php

namespace Modules\Attention\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Enums\ResponseType;
use Modules\Attention\Models\Attention;
use Modules\Attention\Models\AttentionCategory;
use Modules\Attention\Models\AttentionDepartment;
use Modules\Attention\Models\AttentionNote;
use Modules\Attention\Models\AttentionSede;
use Modules\Attention\Models\AttentionType;
use Throwable;

/**
 * Web controller for PQRSF views
 * Handles web interface for attention management
 */
class AttentionWebController extends Controller
{
    /**
     * Display pending PQRSF list
     */
    public function pending(Request $request)
    {
        $search = $request->get('search');
        $typeId = $request->get('type_id');
        $categoryId = $request->get('category_id');
        $departmentId = $request->get('department_id');
        $sedeId = $request->get('sede_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Base query for pending attentions
        $query = Attention::with(['type', 'category', 'assignedUser', 'department', 'sede'])
            ->whereIn('status', [AttentionStatus::RECEIVED, AttentionStatus::IN_PROCESS]);

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('radicado', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('customer_firstname', 'like', "%{$search}%")
                    ->orWhere('customer_lastname', 'like', "%{$search}%")
                    ->orWhere('customer_dni', 'like', "%{$search}%");
            });
        }

        // Apply type filter
        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        // Apply category filter
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        // Apply department filter
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        // Apply sede filter
        if ($sedeId) {
            $query->where('sede_id', $sedeId);
        }

        // Apply date range filter
        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        // Filter by user permissions
        if (! auth()->user()->hasRole('super-settings')) {
            $query->where(function ($q) {
                $q->where('assigned_user_id', auth()->id())
                    ->orWhereIn('department_id', $this->getUserDepartments(auth()->user()));
            });
        }

        // Get paginated results
        $attentions = $query->latest()->paginate(20)->withQueryString();

        // Calculate stats
        $stats = $this->calculateStats($query);

        // Get filter options
        $types = AttentionType::orderBy('name')->get();
        $categories = AttentionCategory::orderBy('name')->get();
        $departments = AttentionDepartment::orderBy('name')->get();
        $sedes = AttentionSede::orderBy('name')->get();

        return view('attention::attentions.pending', [
            'attentions' => $attentions,
            'types' => $types,
            'categories' => $categories,
            'departments' => $departments,
            'sedes' => $sedes,
            'searchKey' => $search,
            'typeId' => $typeId,
            'categoryId' => $categoryId,
            'departmentId' => $departmentId,
            'sedeId' => $sedeId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'stats' => $stats,
        ]);
    }

    /**
     * Display all PQRSF list
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $statusFilter = $request->get('status');
        $typeId = $request->get('type_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Base query
        $query = Attention::with(['type', 'category', 'assignedUser', 'department', 'sede']);

        // Apply search filter
        if ($search) {
            $query->search($search);
        }

        // Apply status filter
        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        // Apply type filter
        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        // Apply date range filter
        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        // Get paginated results
        $attentions = $query->latest()->paginate(20)->withQueryString();

        // Get filter options
        $types = AttentionType::orderBy('name')->get();

        return view('attention::attentions.index', [
            'attentions' => $attentions,
            'types' => $types,
            'searchKey' => $search,
            'statusFilter' => $statusFilter,
            'typeId' => $typeId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    /**
     * Show the form for creating a new PQRSF
     */
    public function create()
    {
        $types = AttentionType::orderBy('name')->get();
        $categories = AttentionCategory::orderBy('name')->get();
        $sedes = AttentionSede::orderBy('name')->get();

        return view('attention::attentions.create', [
            'types' => $types,
            'categories' => $categories,
            'sedes' => $sedes,
        ]);
    }

    /**
     * Store a newly created PQRSF
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type_id' => 'required|exists:attention_types,id',
            'category_id' => 'required|exists:attention_categories,id',
            'sede_id' => 'required|exists:attention_sedes,id',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'is_anonymous' => 'nullable|boolean',
            'customer_firstname' => 'required_unless:is_anonymous,1|nullable|string|max:100',
            'customer_lastname' => 'required_unless:is_anonymous,1|nullable|string|max:100',
            'customer_dni' => 'required_unless:is_anonymous,1|nullable|string|max:30',
            'customer_email' => 'required_unless:is_anonymous,1|nullable|email|max:150',
            'customer_cellphone' => 'required_unless:is_anonymous,1|nullable|string|max:20',
            'customer_address' => 'nullable|string|max:255',
            'attachments.*' => 'nullable|file|max:5120|mimes:pdf,doc,docx,jpg,jpeg,png',
        ]);

        $validated['radicado'] = Attention::generateRadicado();
        $validated['status'] = AttentionStatus::RECEIVED;
        $validated['is_anonymous'] = $request->boolean('is_anonymous');

        $attention = Attention::create($validated);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attention->addMedia($file)->toMediaCollection('attachments');
            }
        }

        $attention->logAction('created', 'PQRSF radicado desde panel administrativo');

        return redirect()->route('attention.show', $attention->uid)
            ->with('success', "PQRSF radicado exitosamente con número {$attention->radicado}");
    }

    /**
     * Show the form for editing the specified PQRSF
     */
    public function edit(string $uid)
    {
        $attention = Attention::with(['type', 'category', 'sede'])
            ->where('uid', $uid)->firstOrFail();

        if (! $attention->canBeEdited()) {
            return redirect()->route('attention.show', $attention->uid)
                ->with('error', 'Este PQRSF ya no puede ser editado');
        }

        $types = AttentionType::orderBy('name')->get();
        $categories = AttentionCategory::orderBy('name')->get();
        $sedes = AttentionSede::orderBy('name')->get();
        $departments = AttentionDepartment::orderBy('name')->get();
        $users = User::orderBy('firstname')->get();
        $statuses = AttentionStatus::cases();
        $responseTypes = ResponseType::cases();

        return view('attention::attentions.manage', [
            'attention' => $attention,
            'types' => $types,
            'categories' => $categories,
            'sedes' => $sedes,
            'departments' => $departments,
            'users' => $users,
            'statuses' => $statuses,
            'responseTypes' => $responseTypes,
        ]);
    }

    /**
     * Display the specified PQRSF
     */
    public function show(string $uid)
    {
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
        ])->where('uid', $uid)->firstOrFail();

        return view('attention::attentions.show', [
            'attention' => $attention,
        ]);
    }

    /**
     * Show management page for PQRSF
     */
    public function manage(string $uid)
    {
        $attention = Attention::with([
            'type',
            'category',
            'sede',
            'department',
            'assignedUser',
            'notes.user',
            'actions.user',
            'mails',
        ])->where('uid', $uid)->firstOrFail();

        // Get all available statuses
        $statuses = AttentionStatus::cases();

        // Get departments
        $departments = AttentionDepartment::orderBy('name')->get();

        // Get users for assignment
        $users = User::orderBy('firstname')->get();

        // Get response types
        $responseTypes = ResponseType::cases();

        return view('attention::attentions.manage', [
            'attention' => $attention,
            'statuses' => $statuses,
            'departments' => $departments,
            'users' => $users,
            'responseTypes' => $responseTypes,
        ]);
    }

    /**
     * Show tracking page
     */
    public function tracking(?string $radicado = null)
    {
        if (! $radicado) {
            return view('attention::attentions.tracking');
        }

        $attention = Attention::with([
            'type',
            'category',
            'sede',
            'department',
            'assignedUser',
            'actions.user',
        ])->byRadicado($radicado)->firstOrFail();

        return view('attention::attentions.tracking', [
            'attention' => $attention,
        ]);
    }

    /**
     * Show emails page
     */
    public function emails(string $uid)
    {
        $attention = Attention::with([
            'mails',
        ])->where('uid', $uid)->firstOrFail();

        return view('attention::attentions.emails', [
            'attention' => $attention,
        ]);
    }

    /**
     * Show satisfaction survey page
     */
    public function survey(string $radicado)
    {
        $attention = Attention::byRadicado($radicado)->firstOrFail();

        // Validation: Must be resolved or closed
        if (! $attention->isResolved() && ! $attention->isClosed()) {
            return redirect()->back()->with('error', 'Solo puede calificar solicitudes resueltas o cerradas');
        }

        return view('attention::attentions.survey', [
            'attention' => $attention,
        ]);
    }

    /**
     * Store a satisfaction survey
     */
    public function storeSurvey(Request $request, string $radicado)
    {
        $attention = Attention::byRadicado($radicado)->firstOrFail();

        $validated = $request->validate([
            'satisfaction_rating' => 'required|integer|min:1|max:5',
            'comments' => 'nullable|string|max:1000',
        ]);

        $attention->update(['satisfaction_rating' => $validated['satisfaction_rating']]);

        $attention->satisfactionSurveys()->create([
            'rating' => $validated['satisfaction_rating'],
            'comments' => $validated['comments'] ?? null,
        ]);

        $attention->logAction('survey_completed', "Calificación: {$validated['satisfaction_rating']}/5");

        return redirect()->route('attention.tracking', $attention->radicado)
            ->with('success', 'Gracias por su calificación');
    }

    // =========================================================================
    // AJAX ENDPOINTS (Web-authenticated, JSON responses)
    // =========================================================================

    /**
     * Add note to a PQRSF
     * POST /attentions/{uid}/notes
     */
    public function addNote(Request $request, string $uid): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|min:3|max:2000',
        ]);

        try {
            $attention = Attention::where('uid', $uid)->firstOrFail();
            $note = $attention->addNote($request->content, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Nota agregada exitosamente',
                'data' => $note->load('user'),
            ], 201);

        } catch (Throwable $e) {
            Log::error('Error adding note', ['uid' => $uid, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al agregar la nota',
            ], 500);
        }
    }

    /**
     * Update a note
     * PUT /attentions/{uid}/notes/{noteId}
     */
    public function updateNote(Request $request, string $uid, int $noteId): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|min:3|max:2000',
        ]);

        try {
            $attention = Attention::where('uid', $uid)->firstOrFail();
            $note = AttentionNote::where('id', $noteId)
                ->where('attention_id', $attention->id)
                ->firstOrFail();

            $note->update(['content' => $request->content]);

            return response()->json([
                'success' => true,
                'message' => 'Nota actualizada correctamente',
                'data' => $note->fresh()->load('user'),
            ]);

        } catch (Throwable $e) {
            Log::error('Error updating note', ['uid' => $uid, 'noteId' => $noteId, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la nota',
            ], 500);
        }
    }

    /**
     * Delete a note
     * DELETE /attentions/{uid}/notes/{noteId}
     */
    public function deleteNote(string $uid, int $noteId): JsonResponse
    {
        try {
            $attention = Attention::where('uid', $uid)->firstOrFail();
            $note = AttentionNote::where('id', $noteId)
                ->where('attention_id', $attention->id)
                ->firstOrFail();

            $note->delete();

            return response()->json([
                'success' => true,
                'message' => 'Nota eliminada correctamente',
            ]);

        } catch (Throwable $e) {
            Log::error('Error deleting note', ['uid' => $uid, 'noteId' => $noteId, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la nota',
            ], 500);
        }
    }

    /**
     * Update management data (status, department, user, resolution)
     * PUT /attentions/{uid}/manage
     */
    public function updateManagement(Request $request, string $uid): JsonResponse
    {
        try {
            DB::beginTransaction();

            $attention = Attention::where('uid', $uid)->firstOrFail();
            $changes = [];

            // Update status
            if ($request->filled('status') && $request->status !== $attention->status->value) {
                $newStatus = AttentionStatus::from($request->status);
                $oldStatus = $attention->status;
                $attention->status = $newStatus;
                $changes[] = "Estado: {$oldStatus->label()} → {$newStatus->label()}";
            }

            // Update department
            if ($request->has('department_id') && $request->department_id != $attention->department_id) {
                $attention->department_id = $request->department_id ?: null;
                $deptName = $request->department_id
                    ? AttentionDepartment::find($request->department_id)?->name ?? 'Desconocido'
                    : 'Sin asignar';
                $changes[] = "Departamento: {$deptName}";
            }

            // Update assigned user
            if ($request->has('assigned_user_id') && $request->assigned_user_id != $attention->assigned_user_id) {
                $attention->assigned_user_id = $request->assigned_user_id ?: null;
                $userName = $request->assigned_user_id
                    ? User::find($request->assigned_user_id)?->name ?? 'Desconocido'
                    : 'Sin asignar';
                $changes[] = "Responsable: {$userName}";
            }

            // Update resolution
            if ($request->filled('resolution')) {
                $attention->resolution = $request->resolution;
                $attention->resolved_at = $attention->resolved_at ?? now();
                $changes[] = 'Resolución actualizada';
            }

            // Update response type
            if ($request->filled('response_type')) {
                $attention->response_type = ResponseType::from($request->response_type);
            }

            if (empty($changes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay cambios para guardar',
                ], 422);
            }

            $attention->save();

            // Log the action
            $description = implode('. ', $changes);
            if ($request->filled('comment')) {
                $description .= ". Comentario: {$request->comment}";
            }
            $attention->logAction('management_updated', $description, auth()->id());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cambios guardados correctamente',
            ]);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Error updating management', ['uid' => $uid, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar los cambios',
            ], 500);
        }
    }

    /**
     * Upload attachment file
     * POST /attentions/{uid}/upload-attachment
     */
    public function uploadAttachment(Request $request, string $uid): JsonResponse
    {
        $request->validate([
            'attachment' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
        ]);

        try {
            $attention = Attention::where('uid', $uid)->firstOrFail();

            if ($attention->isClosed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pueden agregar archivos a una solicitud cerrada',
                ], 422);
            }

            $media = $attention->addMedia($request->file('attachment'))
                ->withCustomProperties(['uploaded_by' => auth()->id()])
                ->toMediaCollection('attachments');

            $attention->logAction('file_uploaded', "Archivo subido: {$media->file_name}", auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Archivo cargado correctamente',
            ]);

        } catch (Throwable $e) {
            Log::error('Error uploading attachment', ['uid' => $uid, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar el archivo',
            ], 500);
        }
    }

    /**
     * Delete attachment file
     * DELETE /attentions/{uid}/delete-attachment/{mediaId}
     */
    public function deleteAttachment(string $uid, int $mediaId): JsonResponse
    {
        try {
            $attention = Attention::where('uid', $uid)->firstOrFail();

            if ($attention->isClosed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pueden eliminar archivos de una solicitud cerrada',
                ], 422);
            }

            $media = $attention->getMedia('attachments')->firstWhere('id', $mediaId);

            if (! $media) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado',
                ], 404);
            }

            $fileName = $media->file_name;
            $media->delete();

            $attention->logAction('file_deleted', "Archivo eliminado: {$fileName}", auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Archivo eliminado correctamente',
            ]);

        } catch (Throwable $e) {
            Log::error('Error deleting attachment', ['uid' => $uid, 'mediaId' => $mediaId, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el archivo',
            ], 500);
        }
    }

    /**
     * Get user departments
     *
     * @param  \App\Models\User  $user
     */
    private function getUserDepartments($user): array
    {
        // TODO: Implement logic to get user departments from groups or roles
        // For now, return empty array
        // This should be implemented based on your user-department relationship
        return [];
    }

    /**
     * Calculate statistics for pending attentions
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    private function calculateStats($baseQuery): array
    {
        // Clone queries to avoid modifying the original
        $totalQuery = clone $baseQuery;
        $todayQuery = clone $baseQuery;
        $assignedQuery = clone $baseQuery;

        // Total pending
        $total = $totalQuery->count();

        // Today's count
        $today = $todayQuery->whereDate('created_at', today())->count();

        // Assigned to current user
        $assignedToMe = $assignedQuery->where('assigned_user_id', auth()->id())->count();

        // Overdue count (older than 5 days for example)
        // You can adjust this based on your SLA configuration
        $overdueQuery = clone $baseQuery;
        $overdue = $overdueQuery->where('created_at', '<', now()->subDays(5))->count();

        return [
            'total' => $total,
            'today' => $today,
            'assigned_to_me' => $assignedToMe,
            'overdue' => $overdue,
        ];
    }
}
