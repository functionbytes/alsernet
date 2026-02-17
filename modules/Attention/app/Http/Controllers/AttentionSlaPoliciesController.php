<?php

namespace Modules\Attention\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Attention\Models\AttentionSlaBreach;
use Modules\Attention\Models\AttentionSlaPolicy;

class AttentionSlaPoliciesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $policies = AttentionSlaPolicy::withCount(['attentions', 'breaches'])
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        // Get breach statistics
        $stats = [
            'total_breaches' => AttentionSlaBreach::count(),
            'unresolved_breaches' => AttentionSlaBreach::unresolved()->count(),
            'escalated_breaches' => AttentionSlaBreach::escalated()->count(),
            'breaches_by_type' => AttentionSlaBreach::select('breach_type', DB::raw('count(*) as count'))
                ->groupBy('breach_type')
                ->pluck('count', 'breach_type')
                ->toArray(),
        ];

        return view('attention::settings.sla-policies.index', compact('policies', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('attention::settings.sla-policies.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:attention_sla_policies,name',
            'description' => 'nullable|string',
            'response_time' => 'required|integer|min:1',
            'resolution_time' => 'required|integer|min:1',
            'closure_time' => 'required|integer|min:1',
            'business_hours_only' => 'boolean',
            'business_hours' => 'nullable|array',
            'timezone' => 'required|string',
            'type_multipliers' => 'nullable|array',
            'enable_escalation' => 'boolean',
            'escalation_threshold_percent' => 'nullable|integer|min:1|max:100',
            'escalation_recipients' => 'nullable|array',
            'active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        AttentionSlaPolicy::create($validated);

        return redirect()
            ->route('settings.attention.sla-policies.index')
            ->with('success', 'Política SLA creada exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(AttentionSlaPolicy $policy)
    {
        $policy->load(['attentions', 'breaches.attention']);

        // Get breach statistics for this policy
        $breachStats = [
            'total' => $policy->breaches()->count(),
            'unresolved' => $policy->breaches()->unresolved()->count(),
            'escalated' => $policy->breaches()->escalated()->count(),
            'by_type' => $policy->breaches()
                ->select('breach_type', DB::raw('count(*) as count'))
                ->groupBy('breach_type')
                ->pluck('count', 'breach_type')
                ->toArray(),
            'recent' => $policy->breaches()
                ->with('attention')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
        ];

        return view('attention::settings.sla-policies.show', compact('policy', 'breachStats'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AttentionSlaPolicy $policy)
    {
        return view('attention::settings.sla-policies.edit', compact('policy'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AttentionSlaPolicy $policy)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:attention_sla_policies,name,'.$policy->id,
            'description' => 'nullable|string',
            'response_time' => 'required|integer|min:1',
            'resolution_time' => 'required|integer|min:1',
            'closure_time' => 'required|integer|min:1',
            'business_hours_only' => 'boolean',
            'business_hours' => 'nullable|array',
            'timezone' => 'required|string',
            'type_multipliers' => 'nullable|array',
            'enable_escalation' => 'boolean',
            'escalation_threshold_percent' => 'nullable|integer|min:1|max:100',
            'escalation_recipients' => 'nullable|array',
            'active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        $policy->update($validated);

        return redirect()
            ->route('settings.attention.sla-policies.index')
            ->with('success', 'Política SLA actualizada exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AttentionSlaPolicy $policy)
    {
        // Prevent deletion of default policy
        if ($policy->is_default) {
            return redirect()
                ->route('settings.attention.sla-policies.index')
                ->with('error', 'No se puede eliminar la política por defecto');
        }

        // Check if policy is in use
        if ($policy->attentions()->count() > 0) {
            return redirect()
                ->route('settings.attention.sla-policies.index')
                ->with('error', 'No se puede eliminar una política que está siendo utilizada');
        }

        $policy->delete();

        return redirect()
            ->route('settings.attention.sla-policies.index')
            ->with('success', 'Política SLA eliminada exitosamente');
    }
}
