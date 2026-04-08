<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Ticket;
use Modules\Helpdesk\Models\TicketCategory;
use Modules\Helpdesk\Models\TicketSlaPolicy;

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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'first_response_time' => 'required|integer|min:1',
            'next_response_time' => 'nullable|integer|min:1',
            'resolution_time' => 'required|integer|min:1',
            'business_hours_only' => 'nullable|boolean',
            'business_hours' => 'nullable|json',
            'timezone' => 'required|string|timezone',
            'priority_multipliers' => 'nullable|json',
            'enable_escalation' => 'nullable|boolean',
            'escalation_threshold_percent' => 'nullable|integer|min:1|max:100',
            'escalation_recipients' => 'nullable|array',
            'active' => 'nullable|boolean',
        ]);

        $validated['business_hours_only'] = $request->boolean('business_hours_only');
        $validated['enable_escalation'] = $request->boolean('enable_escalation');
        $validated['active'] = $request->boolean('active', true);

        // Default business hours if not provided
        if ($validated['business_hours_only'] && empty($validated['business_hours'])) {
            $validated['business_hours'] = json_encode([
                'monday' => ['start' => '09:00', 'end' => '17:00'],
                'tuesday' => ['start' => '09:00', 'end' => '17:00'],
                'wednesday' => ['start' => '09:00', 'end' => '17:00'],
                'thursday' => ['start' => '09:00', 'end' => '17:00'],
                'friday' => ['start' => '09:00', 'end' => '17:00'],
            ]);
        }

        // Default priority multipliers if not provided
        if (empty($validated['priority_multipliers'])) {
            $validated['priority_multipliers'] = json_encode([
                'urgent' => 0.25,
                'high' => 0.5,
                'normal' => 1.0,
                'low' => 2.0,
            ]);
        }

        TicketSlaPolicy::create($validated);

        return redirect()->route('manager.helpdesk.backups.tickets.sla-policies.index')
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
    public function update(Request $request, TicketSlaPolicy $policy)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'first_response_time' => 'required|integer|min:1',
            'next_response_time' => 'nullable|integer|min:1',
            'resolution_time' => 'required|integer|min:1',
            'business_hours_only' => 'nullable|boolean',
            'business_hours' => 'nullable|json',
            'timezone' => 'required|string|timezone',
            'priority_multipliers' => 'nullable|json',
            'enable_escalation' => 'nullable|boolean',
            'escalation_threshold_percent' => 'nullable|integer|min:1|max:100',
            'escalation_recipients' => 'nullable|array',
            'active' => 'nullable|boolean',
        ]);

        $validated['business_hours_only'] = $request->boolean('business_hours_only');
        $validated['enable_escalation'] = $request->boolean('enable_escalation');
        $validated['active'] = $request->boolean('active');

        $policy->update($validated);

        return redirect()->route('manager.helpdesk.backups.tickets.sla-policies.index')
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

        return redirect()->route('manager.helpdesk.backups.tickets.sla-policies.index')
            ->with('success', 'Política SLA eliminada exitosamente.');
    }
}
