<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HelpdeskTickets\Models\Priority;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketTemplate;

class TicketTemplatesController extends Controller
{
    public function index(): View
    {
        $this->authorize('helpdesk.tickets.view');

        $templates = TicketTemplate::query()
            ->with(['category', 'priority'])
            ->orderBy('name')
            ->paginate(20);

        $stats = [
            'total' => TicketTemplate::query()->count(),
            'active' => TicketTemplate::query()->where('is_active', true)->count(),
            'inactive' => TicketTemplate::query()->where('is_active', false)->count(),
            'with_category' => TicketTemplate::query()->whereNotNull('category_id')->count(),
        ];

        return view('helpdesktickets::managers.ticket-templates.index', [
            'templates' => $templates,
            'stats' => $stats,
        ]);
    }

    public function create(): View
    {
        $this->authorize('helpdesk.tickets.create');

        $categories = TicketCategory::active()->ordered()->get();
        $priorities = Priority::where('is_active', true)->orderBy('level')->get();

        return view('helpdesktickets::managers.ticket-templates.form', [
            'template' => null,
            'categories' => $categories,
            'priorities' => $priorities,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('helpdesk.tickets.create');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'category_id' => 'nullable|exists:helpdesk_ticket_categories,id',
            'priority_id' => 'nullable|exists:helpdesk_priorities,id',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        TicketTemplate::create($validated);

        return redirect()
            ->route('manager.helpdesk.ticket-templates.index')
            ->with('success', __('helpdesk::helpdesk.messages.template_created'));
    }

    public function edit(TicketTemplate $ticketTemplate): View
    {
        $this->authorize('helpdesk.tickets.update');

        $categories = TicketCategory::active()->ordered()->get();
        $priorities = Priority::where('is_active', true)->orderBy('level')->get();

        return view('helpdesktickets::managers.ticket-templates.form', [
            'template' => $ticketTemplate,
            'categories' => $categories,
            'priorities' => $priorities,
        ]);
    }

    public function update(Request $request, TicketTemplate $ticketTemplate): RedirectResponse
    {
        $this->authorize('helpdesk.tickets.update');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'category_id' => 'nullable|exists:helpdesk_ticket_categories,id',
            'priority_id' => 'nullable|exists:helpdesk_priorities,id',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $ticketTemplate->update($validated);

        return redirect()
            ->route('manager.helpdesk.ticket-templates.index')
            ->with('success', __('helpdesk::helpdesk.messages.template_updated'));
    }

    public function destroy(TicketTemplate $ticketTemplate): RedirectResponse
    {
        $this->authorize('helpdesk.tickets.delete');

        $ticketTemplate->delete();

        return redirect()
            ->route('manager.helpdesk.ticket-templates.index')
            ->with('success', __('helpdesk::helpdesk.messages.template_deleted'));
    }
}
