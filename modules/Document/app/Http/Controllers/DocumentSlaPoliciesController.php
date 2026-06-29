<?php

namespace Modules\Document\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentSlaPolicy;

class DocumentSlaPoliciesController extends Controller
{
    /**
     * Display a listing of SLA policies.
     */
    public function index(Request $request)
    {
        $query = DocumentSlaPolicy::query();

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
            'total' => DocumentSlaPolicy::count(),
            'active' => DocumentSlaPolicy::where('active', true)->count(),
            'inactive' => DocumentSlaPolicy::where('active', false)->count(),
            'with_escalation' => DocumentSlaPolicy::where('enable_escalation', true)->count(),
        ];

        return view('documents::settings.sla-policies.index', [
            'policies' => $policies,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new SLA policy.
     */
    public function create()
    {
        return view('documents::settings.sla-policies.create');
    }

    /**
     * Display the specified SLA policy (redirect to edit).
     */
    public function show(DocumentSlaPolicy $policy)
    {
        return redirect()->route('settings.documents.sla-policies.edit', $policy->id);
    }

    /**
     * Store a newly created SLA policy.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:document_sla_policies',
            'description' => 'nullable|string|max:1000',
            'upload_request_time' => 'required|integer|min:1',
            'review_time' => 'nullable|integer|min:1',
            'approval_time' => 'required|integer|min:1',
            'business_hours_only' => 'nullable|boolean',
            'business_hours' => 'nullable|json',
            'timezone' => 'required|string|timezone',
            'document_type_multipliers' => 'nullable|json',
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

        // Default document type multipliers if not provided
        if (empty($validated['document_type_multipliers'])) {
            $validated['document_type_multipliers'] = json_encode([
                'corta' => 0.75,
                'rifle' => 1.0,
                'escopeta' => 1.0,
                'dni' => 0.5,
                'general' => 1.0,
                'order' => 1.5,
            ]);
        }

        DocumentSlaPolicy::create($validated);

        return redirect()->route('settings.documents.sla-policies.index')
            ->with('success', 'Política SLA creada exitosamente.');
    }

    /**
     * Show the form for editing an SLA policy.
     */
    public function edit(DocumentSlaPolicy $policy)
    {
        return view('documents::settings.sla-policies.edit', compact('policy'));
    }

    /**
     * Update the specified SLA policy.
     */
    public function update(Request $request, DocumentSlaPolicy $policy)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:document_sla_policies,name,'.$policy->id,
            'description' => 'nullable|string|max:1000',
            'upload_request_time' => 'required|integer|min:1',
            'review_time' => 'nullable|integer|min:1',
            'approval_time' => 'required|integer|min:1',
            'business_hours_only' => 'nullable|boolean',
            'business_hours' => 'nullable|json',
            'timezone' => 'required|string|timezone',
            'document_type_multipliers' => 'nullable|json',
            'enable_escalation' => 'nullable|boolean',
            'escalation_threshold_percent' => 'nullable|integer|min:1|max:100',
            'escalation_recipients' => 'nullable|array',
            'active' => 'nullable|boolean',
        ]);

        $validated['business_hours_only'] = $request->boolean('business_hours_only');
        $validated['enable_escalation'] = $request->boolean('enable_escalation');
        $validated['active'] = $request->boolean('active');

        $policy->update($validated);

        return redirect()->route('settings.documents.sla-policies.index')
            ->with('success', 'Política SLA actualizada exitosamente.');
    }

    /**
     * Toggle the active status of an SLA policy.
     */
    public function toggle(DocumentSlaPolicy $policy)
    {
        $policy->update(['active' => ! $policy->active]);

        return back()->with('success', 'Estado de la política SLA actualizado exitosamente.');
    }

    /**
     * Remove the specified SLA policy.
     */
    public function destroy(DocumentSlaPolicy $policy)
    {
        // Check if policy is being used in documents
        $documents = Document::where('sla_policy_id', $policy->id)->count();

        if ($documents > 0) {
            return back()->with('error', 'No se puede eliminar una política SLA que está siendo utilizada por '.$documents.' documento(s).');
        }

        $policy->delete();

        return redirect()->route('settings.documents.sla-policies.index')
            ->with('success', 'Política SLA eliminada exitosamente.');
    }
}
