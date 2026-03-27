<?php

namespace Modules\Attention\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Attention\Http\Requests\StoreSlaPolicyRequest;
use Modules\Attention\Http\Requests\UpdateSlaPolicyRequest;
use Modules\Attention\Models\AttentionSlaBreach;
use Modules\Attention\Models\AttentionSlaPolicy;

class AttentionSlaPoliciesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
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
    public function create(): View
    {
        return view('attention::settings.sla-policies.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSlaPolicyRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['enable_escalation'] = (bool) ($validated['enable_escalation'] ?? false);
        $validated['active'] = (bool) ($validated['active'] ?? true);
        $validated['is_default'] = (bool) ($validated['is_default'] ?? false);

        AttentionSlaPolicy::create($validated);

        return redirect()
            ->route('settings.attention.sla-policies.index')
            ->with('success', 'Política SLA creada exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(AttentionSlaPolicy $policy): View
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
    public function edit(AttentionSlaPolicy $policy): View
    {
        return view('attention::settings.sla-policies.edit', compact('policy'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSlaPolicyRequest $request, AttentionSlaPolicy $policy): RedirectResponse
    {
        $validated = $request->validated();
        $validated['enable_escalation'] = (bool) ($validated['enable_escalation'] ?? false);
        $validated['active'] = (bool) ($validated['active'] ?? true);
        $validated['is_default'] = (bool) ($validated['is_default'] ?? false);

        $policy->update($validated);

        return redirect()
            ->route('settings.attention.sla-policies.index')
            ->with('success', 'Política SLA actualizada exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AttentionSlaPolicy $policy): RedirectResponse
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
