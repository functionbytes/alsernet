<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\HelpdeskTickets\Http\Requests\Settings\StoreSlaPolicyRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\UpdateSlaPolicyRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketSlaPolicy;

class TicketSlaPoliciesController extends Controller
{
    /**
     * Display a listing of SLA policies.
     */
    public function index(Request $request)
    {
        $query = TicketSlaPolicy::query();

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $policies = $query->latest()->paginate(20);

        // Calculate statistics
        $stats = [
            'total' => TicketSlaPolicy::count(),
            'active' => TicketSlaPolicy::where('active', true)->count(),
            'inactive' => TicketSlaPolicy::where('active', false)->count(),
            'with_escalation' => TicketSlaPolicy::where('enable_escalation', true)->count(),
        ];

        return view('theme.views.backups.helpdesk.ticket-sla-policies.index', [
            'policies' => $policies,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new SLA policy.
     */
    public function create()
    {
        return view('theme.views.backups.helpdesk.ticket-sla-policies.create');
    }

    /**
     * Store a newly created SLA policy.
     */
    public function store(StoreSlaPolicyRequest $request)
    {
        $validated = $request->validated();

        $validated['business_hours_only'] = $request->boolean('business_hours_only');
        $validated['enable_escalation'] = $request->boolean('enable_escalation');
        $validated['active'] = $request->boolean('active', true);

        if ($validated['business_hours_only'] && empty($validated['business_hours'])) {
            $validated['business_hours'] = [
                'monday' => ['start' => '09:00', 'end' => '17:00'],
                'tuesday' => ['start' => '09:00', 'end' => '17:00'],
                'wednesday' => ['start' => '09:00', 'end' => '17:00'],
                'thursday' => ['start' => '09:00', 'end' => '17:00'],
                'friday' => ['start' => '09:00', 'end' => '17:00'],
            ];
        }

        if (empty($validated['priority_multipliers'])) {
            $validated['priority_multipliers'] = [
                'urgent' => 0.25,
                'high' => 0.5,
                'normal' => 1.0,
                'low' => 2.0,
            ];
        }

        TicketSlaPolicy::create($validated);

        return redirect()->route('manager.helpdesk.settings.ticket-sla-policies.index')
            ->with('success', 'Política SLA creada exitosamente.');
    }

    /**
     * Show the form for editing an SLA policy.
     */
    public function edit(TicketSlaPolicy $policy)
    {
        return view('theme.views.backups.helpdesk.ticket-sla-policies.edit', compact('policy'));
    }

    /**
     * Update the specified SLA policy.
     */
    public function update(UpdateSlaPolicyRequest $request, TicketSlaPolicy $policy)
    {
        $validated = $request->validated();

        $validated['business_hours_only'] = $request->boolean('business_hours_only');
        $validated['enable_escalation'] = $request->boolean('enable_escalation');
        $validated['active'] = $request->boolean('active');

        $policy->update($validated);

        return redirect()->route('manager.helpdesk.settings.ticket-sla-policies.index')
            ->with('success', 'Política SLA actualizada exitosamente.');
    }

    /**
     * Toggle the active status of an SLA policy.
     */
    public function toggle(TicketSlaPolicy $policy)
    {
        $policy->update(['active' => ! $policy->active]);

        return back()->with('success', 'Estado de la política SLA actualizado exitosamente.');
    }

    /**
     * Remove the specified SLA policy.
     */
    public function destroy(TicketSlaPolicy $policy)
    {
        // Check if policy is being used
        $categoriesCount = TicketCategory::where('default_sla_policy_id', $policy->id)->count();
        $ticketsCount = Ticket::where('sla_policy_id', $policy->id)->count();

        if ($categoriesCount > 0 || $ticketsCount > 0) {
            return back()->with('error', 'No se puede eliminar una política SLA que está siendo utilizada.');
        }

        $policy->delete();

        return redirect()->route('manager.helpdesk.settings.ticket-sla-policies.index')
            ->with('success', 'Política SLA eliminada exitosamente.');
    }
}
