<?php

namespace Modules\Attention\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Enums\ResponseType;
use Modules\Attention\Jobs\ExportAttentionsJob;
use Modules\Attention\Models\Attention;
use Modules\Attention\Models\AttentionCategory;
use Modules\Attention\Models\AttentionDepartment;
use Modules\Attention\Models\AttentionNote;
use Modules\Attention\Models\AttentionSede;
use Modules\Attention\Models\AttentionType;
use Modules\Attention\Services\AttentionExportService;
use Modules\Attention\Services\AttentionRoutingService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Web controller for peticiones views
 * Handles web interface for attention management
 */
class AttentionWebController extends Controller
{
    /**
     * Display pending peticiones list
     */
    public function pending(Request $request): View
    {
        $this->authorize('viewAny', Attention::class);

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

        // Calculate stats before paginating (pagination adds LIMIT/OFFSET that breaks COUNT queries)
        $stats = $this->calculateStats($query);

        // Get paginated results
        $attentions = $query->latest()->paginate(config('pagination.attentions'))->withQueryString();

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
     * Display all peticiones list
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAll', Attention::class);

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
        $attentions = $query->latest()->paginate(config('pagination.attentions'))->withQueryString();

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
     * Show the form for creating a new peticiones
     */
    public function create(): View
    {
        $this->authorize('create', Attention::class);
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
     * Store a newly created peticiones
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Attention::class);
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

        app(AttentionRoutingService::class)->applyRules($attention);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attention->addMedia($file)->toMediaCollection('attachments');
            }
        }

        $attention->logAction('created', 'peticiones radicado desde panel administrativo');

        return redirect()->route('attention.show', $attention->uid)
            ->with('success', "peticiones radicado exitosamente con número {$attention->radicado}");
    }

    /**
     * Show the form for editing the specified peticiones
     */
    public function edit(string $uid): View|RedirectResponse
    {
        $attention = Attention::with(['type', 'category', 'sede'])
            ->where('uid', $uid)->firstOrFail();

        $this->authorize('update', $attention);

        if (! $attention->canBeEdited()) {
            return redirect()->route('attention.show', $attention->uid)
                ->with('error', 'Este peticiones ya no puede ser editado');
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
     * Display the specified peticiones
     */
    public function show(string $uid): View
    {
        $attention = Attention::where('uid', $uid)->firstOrFail();

        $this->authorize('view', $attention);

        $attention->load([
            'type',
            'category',
            'sede',
            'department',
            'assignedUser',
            'notes.user',
            'actions.user',
            'mails',
            'satisfactionSurveys',
        ]);

        return view('attention::attentions.show', [
            'attention' => $attention,
        ]);
    }

    /**
     * Show management page for peticiones
     */
    public function manage(string $uid): View
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

        $this->authorize('manage', $attention);

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
    public function tracking(?string $radicado = null): View
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
    public function emails(string $uid): View
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
    public function survey(string $radicado): View|RedirectResponse
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
    public function storeSurvey(Request $request, string $radicado): RedirectResponse
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

    /**
     * Queue a peticiones export job; user is notified when ready.
     * GET /attentions/export
     */
    public function export(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('viewAny', Attention::class);
        $validated = $request->validate([
            'format' => ['nullable', 'in:excel,csv,pdf'],
            'status' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $format = $validated['format'] ?? 'excel';

        ExportAttentionsJob::dispatch(auth()->user(), $validated, $format);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'La exportación está en proceso. Recibirás una notificación cuando esté lista.']);
        }

        return redirect()
            ->back()
            ->with('success', 'La exportación está en proceso. Recibirás una notificación cuando esté lista.');
    }

    /**
     * Download a previously generated peticiones export by token.
     * GET /attentions/export/download
     */
    public function exportDownload(Request $request): BinaryFileResponse|RedirectResponse
    {
        $this->authorize('viewAny', Attention::class);
        $token = $request->query('token');

        if (! $token || ! preg_match('/^[a-zA-Z0-9]{32}$/', $token)) {
            abort(400);
        }

        $file = AttentionExportService::getExportFile($token);

        if (! $file) {
            return redirect()
                ->route('attention.pending')
                ->with('error', 'El archivo no existe o ya expiró.');
        }

        $mimeTypes = [
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv',
            'pdf' => 'application/pdf',
        ];

        $mime = $mimeTypes[$file['format']] ?? 'application/octet-stream';

        return response()->download($file['path'], $file['filename'], ['Content-Type' => $mime]);
    }

    // =========================================================================
    // BULK ACTIONS
    // =========================================================================

    /**
     * Execute bulk action on multiple peticiones
     * POST /attentions/bulk-action
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:close,assign,change_status,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:attentions,id',
            'value' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->action === 'change_status' && ! in_array($value, ['received', 'in_process', 'resolved', 'closed'])) {
                        $fail('El estado seleccionado no es válido.');
                    }
                },
            ],
        ]);

        $attentions = Attention::whereIn('id', $request->ids)->get();

        $policyMethod = $request->action === 'delete' ? 'delete' : 'manage';

        foreach ($attentions as $attention) {
            $this->authorize($policyMethod, $attention);
        }

        $query = Attention::whereIn('id', $request->ids);

        match ($request->action) {
            'close' => $query->update(['status' => AttentionStatus::CLOSED->value, 'closed_at' => now()]),
            'assign' => $query->update(['assigned_user_id' => $request->value]),
            'change_status' => $query->update(['status' => $request->value]),
            'delete' => $query->delete(),
        };

        return response()->json(['success' => true, 'count' => $attentions->count()]);
    }

    // =========================================================================
    // AJAX ENDPOINTS (Web-authenticated, JSON responses)
    // =========================================================================

    /**
     * Add note to a peticiones
     * POST /attentions/{uid}/notes
     */
    public function addNote(Request $request, string $uid): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|min:3|max:2000',
        ]);

        $attention = Attention::where('uid', $uid)->firstOrFail();
        $this->authorize('manageNotes', $attention);

        try {
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

        $attention = Attention::where('uid', $uid)->firstOrFail();
        $this->authorize('manageNotes', $attention);

        try {
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
        $attention = Attention::where('uid', $uid)->firstOrFail();
        $this->authorize('manageNotes', $attention);

        try {
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
        $attention = Attention::where('uid', $uid)->firstOrFail();
        $this->authorize('manage', $attention);

        $request->validate([
            'status' => 'nullable|string|in:received,in_process,resolved,closed',
            'department_id' => 'nullable|integer|exists:attention_departments,id',
            'assigned_user_id' => 'nullable|integer|exists:users,id',
            'resolution' => 'nullable|string',
            'response_type' => 'nullable|string',
            'comment' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

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

        $attention = Attention::where('uid', $uid)->firstOrFail();
        $this->authorize('update', $attention);

        try {
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
        $attention = Attention::where('uid', $uid)->firstOrFail();
        $this->authorize('update', $attention);

        try {

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
     * Get IDs of departments the user belongs to via the attention_department_user pivot.
     * Cache is invalidated when department memberships change (see AttentionDepartment observers).
     */
    private function getUserDepartments($user): array
    {
        return Cache::remember("user_departments_{$user->id}", 300, function () use ($user) {
            return AttentionDepartment::query()
                ->whereHas('users', fn ($q) => $q->where('user_id', $user->id))
                ->pluck('id')
                ->toArray();
        });
    }

    /**
     * Calculate statistics for pending attentions using a single query.
     *
     * @param  Builder  $baseQuery
     */
    private function calculateStats($baseQuery): array
    {
        $userId = auth()->id();
        $today = today()->toDateString();
        $overdueThreshold = now()->subDays(5)->toDateTimeString();

        $row = (clone $baseQuery)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as today,
                SUM(CASE WHEN assigned_user_id = ? THEN 1 ELSE 0 END) as assigned_to_me,
                SUM(CASE WHEN created_at < ? THEN 1 ELSE 0 END) as overdue
            ', [$today, $userId, $overdueThreshold])
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'today' => (int) ($row->today ?? 0),
            'assigned_to_me' => (int) ($row->assigned_to_me ?? 0),
            'overdue' => (int) ($row->overdue ?? 0),
        ];
    }
}
