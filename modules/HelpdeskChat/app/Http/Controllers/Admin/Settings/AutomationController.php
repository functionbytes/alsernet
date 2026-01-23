<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Settings;

use Illuminate\Http\Request;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Automations\Automation;

class AutomationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $automationRules = auth()->user()->account->automations()
            ->orderBy('active', 'desc')
            ->orderBy('name')
            ->paginate(20);

        return view('helpdeskchat::admin.settings.automation-rules.index', compact('automationRules'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('helpdeskchat::admin.settings.automation-rules.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_name' => 'required|string|max:255',
            'conditions' => 'required|array',
            'actions' => 'required|array',
            'active' => 'boolean',
        ]);

        auth()->user()->account->automations()->create($validated);

        return redirect()->route('admin.automation-rules.index')
            ->with('success', 'Automation rule created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Automation $Automation)
    {
        $this->authorize('view', $Automation);

        return view('helpdeskchat::admin.settings.automation-rules.show', compact('Automation'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Automation $Automation)
    {
        $this->authorize('update', $Automation);

        return view('helpdeskchat::admin.settings.automation-rules.edit', compact('Automation'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Automation $Automation)
    {
        $this->authorize('update', $Automation);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_name' => 'required|string|max:255',
            'conditions' => 'required|array',
            'actions' => 'required|array',
            'active' => 'boolean',
        ]);

        $Automation->update($validated);

        return redirect()->route('admin.automation-rules.index')
            ->with('success', 'Automation rule updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Automation $Automation)
    {
        $this->authorize('delete', $Automation);

        $Automation->delete();

        return redirect()->route('admin.automation-rules.index')
            ->with('success', 'Automation rule deleted successfully.');
    }

    /**
     * Toggle active status
     */
    public function toggleActive(Automation $Automation)
    {
        $this->authorize('update', $Automation);

        $Automation->update(['active' => ! $Automation->active]);

        return redirect()->back()
            ->with('success', 'Automation rule status updated.');
    }
}
