<?php

namespace Modules\Chat\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Sla\SlaPolicy;

class SlaPolicyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $policies = auth()->user()->account->slaPolicies()
            ->withCount('appliedConversations')
            ->orderBy('name')
            ->paginate(20);

        return view('Chat::settings.sla-policies.index', compact('policies'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('Chat::settings.sla-policies.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'first_response_time_minutes' => 'nullable|integer|min:1',
            'resolution_time_minutes' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'business_hours_only' => 'boolean',
        ]);

        auth()->user()->account->slaPolicies()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'first_response_time_minutes' => $validated['first_response_time_minutes'] ?? null,
            'resolution_time_minutes' => $validated['resolution_time_minutes'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'business_hours_only' => $validated['business_hours_only'] ?? false,
        ]);

        return redirect()->route('settings.chat.sla-policies.index')
            ->with('success', 'SLA Policy created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(SlaPolicy $slaPolicy)
    {
        $this->authorize('view', $slaPolicy);

        $slaPolicy->load(['appliedConversations' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return view('Chat::settings.sla-policies.show', compact('slaPolicy'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SlaPolicy $slaPolicy)
    {
        $this->authorize('update', $slaPolicy);

        return view('Chat::settings.sla-policies.edit', compact('slaPolicy'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SlaPolicy $slaPolicy)
    {
        $this->authorize('update', $slaPolicy);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'first_response_time_minutes' => 'nullable|integer|min:1',
            'resolution_time_minutes' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'business_hours_only' => 'boolean',
        ]);

        $slaPolicy->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'first_response_time_minutes' => $validated['first_response_time_minutes'] ?? null,
            'resolution_time_minutes' => $validated['resolution_time_minutes'] ?? null,
            'is_active' => $validated['is_active'] ?? $slaPolicy->is_active,
            'business_hours_only' => $validated['business_hours_only'] ?? $slaPolicy->business_hours_only,
        ]);

        return redirect()->route('settings.chat.sla-policies.index')
            ->with('success', 'SLA Policy updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SlaPolicy $slaPolicy)
    {
        $this->authorize('delete', $slaPolicy);

        $slaPolicy->delete();

        return redirect()->route('settings.chat.sla-policies.index')
            ->with('success', 'SLA Policy deleted successfully.');
    }
}
