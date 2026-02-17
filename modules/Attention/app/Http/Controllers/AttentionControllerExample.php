<?php

namespace Modules\Attention\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Attention\App\Http\Requests\StoreAttentionRequest;
use Modules\Attention\App\Http\Requests\UpdateAttentionRequest;
use Modules\Attention\App\Models\Attention;

/**
 * Example controller showing how to use AttentionPolicy
 *
 * This is a reference implementation demonstrating proper policy usage.
 * Adapt these patterns to your actual controllers.
 */
class AttentionControllerExample extends Controller
{
    /**
     * Display a listing of attentions.
     */
    public function index(Request $request): View
    {
        // Check if user can view any attentions
        $this->authorize('viewAny', Attention::class);

        // Get accessible attentions for the user
        $attentions = $request->user()->accessibleAttentions()
            ->with(['user', 'assignedUser', 'department', 'type'])
            ->latest()
            ->paginate(20);

        return view('attention::index', compact('attentions'));
    }

    /**
     * Show the form for creating a new attention.
     */
    public function create(): View
    {
        // Check if user can create attentions
        $this->authorize('create', Attention::class);

        return view('attention::create');
    }

    /**
     * Store a newly created attention.
     */
    public function store(StoreAttentionRequest $request): RedirectResponse
    {
        // Check if user can create attentions
        $this->authorize('create', Attention::class);

        // Create the attention
        $attention = Attention::create($request->validated());

        return redirect()
            ->route('attention.show', $attention)
            ->with('success', 'PQRSF creado exitosamente.');
    }

    /**
     * Display the specified attention.
     */
    public function show(string $uid): View
    {
        $attention = Attention::where('uid', $uid)->firstOrFail();

        // Check if user can view this attention
        $this->authorize('view', $attention);

        $attention->load(['user', 'assignedUser', 'department', 'type', 'notes']);

        return view('attention::show', compact('attention'));
    }

    /**
     * Show the form for editing the specified attention.
     */
    public function edit(string $uid): View
    {
        $attention = Attention::where('uid', $uid)->firstOrFail();

        // Check if user can update this attention
        $this->authorize('update', $attention);

        return view('attention::edit', compact('attention'));
    }

    /**
     * Update the specified attention.
     */
    public function update(UpdateAttentionRequest $request, string $uid): RedirectResponse
    {
        $attention = Attention::where('uid', $uid)->firstOrFail();

        // Check if user can update this attention
        $this->authorize('update', $attention);

        $attention->update($request->validated());

        return redirect()
            ->route('attention.show', $attention)
            ->with('success', 'PQRSF actualizado exitosamente.');
    }

    /**
     * Remove the specified attention.
     */
    public function destroy(string $uid): RedirectResponse
    {
        $attention = Attention::where('uid', $uid)->firstOrFail();

        // Check if user can delete this attention
        $this->authorize('delete', $attention);

        $attention->delete();

        return redirect()
            ->route('attention.index')
            ->with('success', 'PQRSF eliminado exitosamente.');
    }

    /**
     * Assign attention to user or department.
     */
    public function assign(Request $request, string $uid): RedirectResponse
    {
        $attention = Attention::where('uid', $uid)->firstOrFail();

        // Check if user can assign this attention
        $this->authorize('assign', $attention);

        $validated = $request->validate([
            'assigned_user_id' => 'nullable|exists:users,id',
            'department_id' => 'nullable|exists:attention_departments,id',
        ]);

        $attention->update($validated);

        return redirect()
            ->route('attention.show', $attention)
            ->with('success', 'PQRSF asignado exitosamente.');
    }

    /**
     * Change the status of attention.
     */
    public function changeStatus(Request $request, string $uid): RedirectResponse
    {
        $attention = Attention::where('uid', $uid)->firstOrFail();

        // Check if user can change status
        $this->authorize('changeStatus', $attention);

        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,resolved,closed',
        ]);

        $attention->update($validated);

        return redirect()
            ->route('attention.show', $attention)
            ->with('success', 'Estado actualizado exitosamente.');
    }

    /**
     * Resolve the attention.
     */
    public function resolve(Request $request, string $uid): RedirectResponse
    {
        $attention = Attention::where('uid', $uid)->firstOrFail();

        // Check if user can resolve this attention
        $this->authorize('resolve', $attention);

        $validated = $request->validate([
            'resolution_notes' => 'required|string|max:1000',
        ]);

        $attention->update([
            'status' => 'resolved',
            'resolution_notes' => $validated['resolution_notes'],
            'resolved_at' => now(),
            'resolved_by' => auth()->id(),
        ]);

        return redirect()
            ->route('attention.show', $attention)
            ->with('success', 'PQRSF resuelto exitosamente.');
    }

    /**
     * Close the attention.
     */
    public function close(string $uid): RedirectResponse
    {
        $attention = Attention::where('uid', $uid)->firstOrFail();

        // Check if user can close this attention
        $this->authorize('close', $attention);

        $attention->update([
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by' => auth()->id(),
        ]);

        return redirect()
            ->route('attention.show', $attention)
            ->with('success', 'PQRSF cerrado exitosamente.');
    }

    /**
     * Send email notification.
     */
    public function sendEmail(Request $request, string $uid): RedirectResponse
    {
        $attention = Attention::where('uid', $uid)->firstOrFail();

        // Check if user can send email for this attention
        $this->authorize('sendEmail', $attention);

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        // Send email logic here...

        return redirect()
            ->route('attention.show', $attention)
            ->with('success', 'Email enviado exitosamente.');
    }

    /**
     * Add a note to the attention.
     */
    public function addNote(Request $request, string $uid): RedirectResponse
    {
        $attention = Attention::where('uid', $uid)->firstOrFail();

        // Check if user can manage notes for this attention
        $this->authorize('manageNotes', $attention);

        $validated = $request->validate([
            'note' => 'required|string|max:1000',
            'is_private' => 'boolean',
        ]);

        $attention->notes()->create([
            'user_id' => auth()->id(),
            'note' => $validated['note'],
            'is_private' => $validated['is_private'] ?? false,
        ]);

        return redirect()
            ->route('attention.show', $attention)
            ->with('success', 'Nota agregada exitosamente.');
    }

    /**
     * View full history of attention.
     */
    public function history(string $uid): View
    {
        $attention = Attention::where('uid', $uid)->firstOrFail();

        // Check if user can view history
        $this->authorize('viewHistory', $attention);

        $attention->load(['notes', 'activities']);

        return view('attention::history', compact('attention'));
    }

    /**
     * View all attentions (settings view).
     */
    public function viewAll(Request $request): View
    {
        // Check if user can view all attentions
        $this->authorize('viewAll', Attention::class);

        $attentions = Attention::with(['user', 'assignedUser', 'department', 'type'])
            ->latest()
            ->paginate(20);

        return view('attention::settings.all', compact('attentions'));
    }

    /**
     * Alternative: Using middleware for authorization
     *
     * Add this to your constructor to use middleware instead of authorize() calls:
     *
     * public function __construct()
     * {
     *     $this->middleware('can:viewAny,Modules\Attention\App\Models\Attention')->only(['index']);
     *     $this->middleware('can:create,Modules\Attention\App\Models\Attention')->only(['create', 'store']);
     *     $this->middleware('can:view,attention')->only(['show']);
     *     $this->middleware('can:update,attention')->only(['edit', 'update']);
     *     $this->middleware('can:delete,attention')->only(['destroy']);
     *     $this->middleware('can:assign,attention')->only(['assign']);
     *     $this->middleware('can:changeStatus,attention')->only(['changeStatus']);
     *     $this->middleware('can:resolve,attention')->only(['resolve']);
     *     $this->middleware('can:close,attention')->only(['close']);
     *     $this->middleware('can:sendEmail,attention')->only(['sendEmail']);
     *     $this->middleware('can:manageNotes,attention')->only(['addNote']);
     *     $this->middleware('can:viewHistory,attention')->only(['history']);
     *     $this->middleware('can:viewAll,Modules\Attention\App\Models\Attention')->only(['viewAll']);
     * }
     *
     * Or use the custom middleware:
     *
     * public function __construct()
     * {
     *     $this->middleware('attention.permission:viewAny')->only(['index']);
     *     $this->middleware('attention.permission:create')->only(['create', 'store']);
     *     $this->middleware('attention.permission:view')->only(['show']);
     *     $this->middleware('attention.permission:update')->only(['edit', 'update']);
     *     $this->middleware('attention.permission:delete')->only(['destroy']);
     *     $this->middleware('attention.permission:assign')->only(['assign']);
     *     $this->middleware('attention.permission:changeStatus')->only(['changeStatus']);
     *     $this->middleware('attention.permission:resolve')->only(['resolve']);
     *     $this->middleware('attention.permission:close')->only(['close']);
     *     $this->middleware('attention.permission:sendEmail')->only(['sendEmail']);
     *     $this->middleware('attention.permission:manageNotes')->only(['addNote']);
     *     $this->middleware('attention.permission:viewHistory')->only(['history']);
     *     $this->middleware('attention.permission:viewAll')->only(['viewAll']);
     * }
     */

    /**
     * Alternative: Using Gate facade for inline checks
     *
     * use Illuminate\Support\Facades\Gate;
     *
     * if (Gate::allows('update', $attention)) {
     *     // User can update
     * }
     *
     * if (Gate::denies('update', $attention)) {
     *     // User cannot update
     * }
     */

    /**
     * Alternative: Using Request user's can method
     *
     * if ($request->user()->can('update', $attention)) {
     *     // User can update
     * }
     *
     * if ($request->user()->cannot('update', $attention)) {
     *     // User cannot update
     * }
     */
}
