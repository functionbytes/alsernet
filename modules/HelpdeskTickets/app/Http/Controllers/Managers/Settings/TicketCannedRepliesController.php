<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\HelpdeskTickets\Http\Requests\Settings\BulkActionTicketCannedReplyRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\StoreTicketCannedReplyRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\UpdateTicketCannedReplyRequest;
use Modules\HelpdeskTickets\Models\TicketCannedReply;
use Modules\HelpdeskTickets\Models\TicketCategory;

class TicketCannedRepliesController extends Controller
{
    public function __construct()
    {
        // duplicate() queda fuera a propósito: vive también detrás de una
        // ruta sin este middleware (ver routes/managers.php, bloque
        // general) porque cualquier agente —tenga o no permiso para
        // gestionar plantillas— puede querer su propia copia editable de
        // una plantilla global desde el modal "Plantillas de email" del
        // propio ticket, no solo desde esta pantalla de ajustes.
        $this->middleware('can:helpdesk.tickets.settings')->except('duplicate');
    }

    /**
     * Display a listing of ticket canned replies.
     */
    public function index(Request $request)
    {
        $query = TicketCannedReply::query();

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->search($search);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        // Filter by global/personal
        if ($request->filled('type')) {
            if ($request->type === 'global') {
                $query->global();
            } elseif ($request->type === 'personal') {
                $query->where('user_id', auth()->id());
            }
        }

        $replies = $query->with(['user', 'ticketCategories'])
            ->latest()
            ->paginate(20);

        // Calculate statistics
        $stats = [
            'total' => TicketCannedReply::count(),
            'global' => TicketCannedReply::where('is_global', true)->count(),
            'personal' => TicketCannedReply::where('user_id', auth()->id())->count(),
            'active' => TicketCannedReply::where('is_active', true)->count(),
        ];

        $categories = TicketCategory::active()->ordered()->get();

        return view('theme.views.backups.helpdesk.ticket-canned-replies.index', [
            'replies' => $replies,
            'stats' => $stats,
            'categories' => $categories,
        ]);
    }

    /**
     * Show the form for creating a new canned reply.
     */
    public function create()
    {
        $categories = TicketCategory::active()->ordered()->get();

        return view('theme.views.backups.helpdesk.ticket-canned-replies.create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created canned reply.
     */
    public function store(StoreTicketCannedReplyRequest $request)
    {
        $validated = $request->validated();

        $validated['user_id'] = auth()->id();
        $validated['is_global'] = $request->boolean('is_global');
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['usage_count'] = 0;

        $reply = TicketCannedReply::create($validated);

        // Attach ticket categories
        if ($request->filled('ticket_categories')) {
            $categoriesData = [];
            foreach ($request->ticket_categories as $index => $categoryId) {
                $categoriesData[$categoryId] = ['order' => $index + 1];
            }
            $reply->ticketCategories()->attach($categoriesData);
        }

        return redirect()->route('manager.helpdesk.settings.ticket-canned-replies.index')
            ->with('success', __('helpdesktickets::helpdesktickets.settings.canned_reply.created'));
    }

    /**
     * Show the form for editing a canned reply.
     */
    public function edit(TicketCannedReply $reply)
    {
        // Check permissions
        if (! $reply->canBeEditedBy(auth()->id())) {
            abort(403, 'No tienes permisos para editar esta respuesta.');
        }

        $reply->load('ticketCategories');
        $categories = TicketCategory::active()->ordered()->get();

        return view('theme.views.backups.helpdesk.ticket-canned-replies.edit', [
            'reply' => $reply,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified canned reply.
     */
    public function update(UpdateTicketCannedReplyRequest $request, TicketCannedReply $reply)
    {
        // Check permissions
        if (! $reply->canBeEditedBy(auth()->id())) {
            abort(403, 'No tienes permisos para editar esta respuesta.');
        }

        $validated = $request->validated();

        $validated['is_global'] = $request->boolean('is_global');
        $validated['is_active'] = $request->boolean('is_active');

        $reply->update($validated);

        // Sync ticket categories
        if ($request->has('ticket_categories')) {
            $categoriesData = [];
            if ($request->filled('ticket_categories')) {
                foreach ($request->ticket_categories as $index => $categoryId) {
                    $categoriesData[$categoryId] = ['order' => $index + 1];
                }
            }
            $reply->ticketCategories()->sync($categoriesData);
        }

        return redirect()->route('manager.helpdesk.settings.ticket-canned-replies.index')
            ->with('success', __('helpdesktickets::helpdesktickets.settings.canned_reply.updated'));
    }

    /**
     * Remove the specified canned reply.
     */
    public function destroy(TicketCannedReply $reply)
    {
        // Check permissions
        if (! $reply->canBeEditedBy(auth()->id())) {
            abort(403, 'No tienes permisos para eliminar esta respuesta.');
        }

        $reply->delete();

        return redirect()->route('manager.helpdesk.settings.ticket-canned-replies.index')
            ->with('success', __('helpdesktickets::helpdesktickets.settings.canned_reply.deleted'));
    }

    /**
     * Apply a bulk action (activate, deactivate or delete) to several canned replies.
     */
    public function bulkAction(BulkActionTicketCannedReplyRequest $request): JsonResponse
    {
        $action = $request->validated('action');
        $ids = $request->validated('ids');
        $count = 0;
        $skipped = 0;

        $replies = TicketCannedReply::whereIn('id', $ids)->get();

        if ($action === 'delete') {
            foreach ($replies as $reply) {
                if (! $reply->canBeEditedBy(auth()->id())) {
                    $skipped++;

                    continue;
                }

                $reply->delete();
                $count++;
            }
        } else {
            $value = $action === 'activate';
            foreach ($replies as $reply) {
                if (! $reply->canBeEditedBy(auth()->id())) {
                    $skipped++;

                    continue;
                }

                $reply->update(['is_active' => $value]);
                $count++;
            }
        }

        $labels = ['delete' => 'eliminada(s)', 'activate' => 'activada(s)', 'deactivate' => 'desactivada(s)'];
        $message = "{$count} respuesta(s) {$labels[$action]}.";
        if ($skipped > 0) {
            $message .= " {$skipped} omitida(s) por no tener permiso para editarlas.";
        }

        return response()->json(['message' => $message, 'count' => $count, 'skipped' => $skipped]);
    }

    /**
     * "Duplicar como mía": cualquier agente que puede VER esta respuesta
     * (global, o su propia personal) puede clonarla para tener una copia
     * propia editable — sin depender de helpdesk.tickets.settings, que es
     * justo el permiso que le faltaría para editar la original si es
     * global (ver __construct()). Se llama tanto desde esta pantalla de
     * ajustes como, sobre todo, desde el modal "Plantillas de email" del
     * propio ticket (cualquier agente, tenga o no ese permiso).
     *
     * La copia nace SIEMPRE personal (is_global=false) aunque el original
     * fuera compartido — de eso se trata, una versión propia que no
     * dependa de permisos de gestión — y sin short_code: es una columna
     * única y el original ya lo tiene ocupado.
     */
    public function duplicate(Request $request, TicketCannedReply $reply): JsonResponse|RedirectResponse
    {
        if (! $reply->is_global && $reply->user_id !== $request->user()->id) {
            abort(403, 'No tienes acceso a esta respuesta.');
        }

        $copy = $reply->replicate(['user_id', 'is_global', 'usage_count', 'short_code']);
        $copy->title = $reply->title.' (copia)';
        $copy->user_id = $request->user()->id;
        $copy->is_global = false;
        $copy->is_active = true;
        $copy->usage_count = 0;
        $copy->short_code = null;
        $copy->save();

        $message = 'Plantilla duplicada. Ya es tuya: edítala como quieras.';

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'reply' => [
                    'id' => $copy->id,
                    'title' => $copy->title,
                    'content' => $copy->content,
                    'short_code' => $copy->short_code,
                ],
            ]);
        }

        return redirect()
            ->route('manager.helpdesk.settings.ticket-canned-replies.edit', $copy->id)
            ->with('success', $message);
    }
}
