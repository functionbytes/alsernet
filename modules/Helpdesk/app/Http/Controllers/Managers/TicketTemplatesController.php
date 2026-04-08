<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Priority;
use Modules\Helpdesk\Models\TicketCategory;
use Modules\Helpdesk\Models\TicketTemplate;

class TicketTemplatesController extends Controller
{
    public function index(): View
    {
        $this->authorize('manager.helpdesk.tickets.index');

        $templates = TicketTemplate::query()
            ->with(['category', 'priority'])
            ->orderBy('name')
            ->paginate(20);

        return view('helpdesk::managers.helpdesk.ticket-templates.index', [
            'templates' => $templates,
        ]);
    }

    public function create(): View
    {
        $this->authorize('manager.helpdesk.tickets.create');

        $categories = TicketCategory::active()->ordered()->get();
        $priorities = Priority::where('is_active', true)->orderBy('level')->get();

        return view('helpdesk::managers.helpdesk.ticket-templates.form', [
            'template' => null,
            'categories' => $categories,
            'priorities' => $priorities,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manager.helpdesk.tickets.create');

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
            ->route('ticket-templates.index')
            ->with('success', __('helpdesk::helpdesk.messages.template_created'));
    }

    public function edit(TicketTemplate $ticketTemplate): View
    {
        $this->authorize('manager.helpdesk.tickets.update');

        $categories = TicketCategory::active()->ordered()->get();
        $priorities = Priority::where('is_active', true)->orderBy('level')->get();

        return view('helpdesk::managers.helpdesk.ticket-templates.form', [
            'template' => $ticketTemplate,
            'categories' => $categories,
            'priorities' => $priorities,
        ]);
    }

    public function update(Request $request, TicketTemplate $ticketTemplate): RedirectResponse
    {
        $this->authorize('manager.helpdesk.tickets.update');

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
            ->route('ticket-templates.index')
            ->with('success', __('helpdesk::helpdesk.messages.template_updated'));
    }

    public function destroy(TicketTemplate $ticketTemplate): RedirectResponse
    {
        $this->authorize('manager.helpdesk.tickets.delete');

        $ticketTemplate->delete();

        return redirect()
            ->route('ticket-templates.index')
            ->with('success', __('helpdesk::helpdesk.messages.template_deleted'));
    }
}
