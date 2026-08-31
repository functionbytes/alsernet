<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HelpdeskTickets\Http\Requests\Managers\StoreTicketTemplateRequest;
use Modules\HelpdeskTickets\Http\Requests\Managers\UpdateTicketTemplateRequest;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketTemplate;

class TicketTemplatesController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', TicketTemplate::class);

        $userId = $request->user()->id;

        $general = TicketTemplate::query()
            ->general()
            ->with('category')
            ->orderBy('name')
            ->paginate(20, ['*'], 'general_page');

        $mine = TicketTemplate::query()
            ->ownedBy($userId)
            ->with('category')
            ->orderBy('name')
            ->paginate(20, ['*'], 'mine_page');

        $stats = [
            'total' => TicketTemplate::query()->count(),
            'general' => TicketTemplate::query()->general()->count(),
            'mine' => TicketTemplate::query()->ownedBy($userId)->count(),
            'active' => TicketTemplate::query()->where('is_active', true)->count(),
        ];

        return view('helpdesktickets::managers.ticket-templates.index', [
            'general' => $general,
            'mine' => $mine,
            'stats' => $stats,
            'canManageGeneral' => $request->user()->can('helpdesk.tickets.manage'),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', TicketTemplate::class);

        $categories = TicketCategory::active()->ordered()->get();

        return view('helpdesktickets::managers.ticket-templates.form', [
            'template' => null,
            'categories' => $categories,
            'canManageGeneral' => $request->user()->can('helpdesk.tickets.manage'),
        ]);
    }

    public function store(StoreTicketTemplateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $isGeneral = $request->boolean('is_general') && $request->user()->can('helpdesk.tickets.manage');
        unset($validated['is_general']);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['created_by'] = $isGeneral ? null : $request->user()->id;

        TicketTemplate::create($validated);

        return redirect()
            ->route('manager.helpdesk.ticket-templates.index')
            ->with('success', __('helpdesktickets::helpdesktickets.messages.template_created'));
    }

    public function edit(Request $request, TicketTemplate $ticketTemplate): View
    {
        $this->authorize('update', $ticketTemplate);

        $categories = TicketCategory::active()->ordered()->get();

        return view('helpdesktickets::managers.ticket-templates.form', [
            'template' => $ticketTemplate,
            'categories' => $categories,
            'canManageGeneral' => $request->user()->can('helpdesk.tickets.manage'),
        ]);
    }

    public function update(UpdateTicketTemplateRequest $request, TicketTemplate $ticketTemplate): RedirectResponse
    {
        $validated = $request->validated();

        $canManageGeneral = $request->user()->can('helpdesk.tickets.manage');
        $isGeneral = $canManageGeneral ? $request->boolean('is_general') : $ticketTemplate->isGeneral();
        unset($validated['is_general']);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['created_by'] = $isGeneral ? null : ($ticketTemplate->created_by ?? $request->user()->id);

        $ticketTemplate->update($validated);

        return redirect()
            ->route('manager.helpdesk.ticket-templates.index')
            ->with('success', __('helpdesktickets::helpdesktickets.messages.template_updated'));
    }

    public function destroy(TicketTemplate $ticketTemplate): RedirectResponse
    {
        $this->authorize('delete', $ticketTemplate);

        $ticketTemplate->delete();

        return redirect()
            ->route('manager.helpdesk.ticket-templates.index')
            ->with('success', __('helpdesktickets::helpdesktickets.messages.template_deleted'));
    }
}
