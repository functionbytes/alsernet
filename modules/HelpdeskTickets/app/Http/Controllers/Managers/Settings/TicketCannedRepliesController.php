<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\HelpdeskTickets\Http\Requests\Settings\StoreTicketCannedReplyRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\UpdateTicketCannedReplyRequest;
use Modules\HelpdeskTickets\Models\TicketCannedReply;
use Modules\HelpdeskTickets\Models\TicketCategory;

class TicketCannedRepliesController extends Controller
{
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
            ->with('success', 'Respuesta enlatada creada exitosamente.');
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
            ->with('success', 'Respuesta enlatada actualizada exitosamente.');
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
            ->with('success', 'Respuesta enlatada eliminada exitosamente.');
    }
}
