<?php

namespace Modules\Chat\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Automations\Automation;

class AutomationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = auth()->user()->account->automations();

        if ($request->input('tab') === 'trashed') {
            $query->onlyTrashed();
        }

        $automationRules = $query->orderBy('active', 'desc')
            ->orderBy('name')
            ->paginate(20);

        $stats = [
            'total' => auth()->user()->account->automations()->count(),
            'active' => auth()->user()->account->automations()->where('active', true)->count(),
            'inactive' => auth()->user()->account->automations()->where('active', false)->count(),
            'trashed' => auth()->user()->account->automations()->onlyTrashed()->count(),
        ];

        return view('Chat::settings.automation-rules.index', compact('automationRules', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Automation::class);

        $account = auth()->user()->account;
        $users = $account->users()->orderBy('firstname')->get();
        $teams = $account->teams;
        $labels = $account->labels;

        return view('Chat::settings.automation-rules.create', compact('users', 'teams', 'labels'));
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

        return redirect()->route('settings.chat.automation-rules.index')
            ->with('success', 'Automation rule created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Automation $automation_rule)
    {
        $this->authorize('view', $automation_rule);

        return view('Chat::settings.automation-rules.show', ['Automation' => $automation_rule]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Automation $automation_rule)
    {
        $this->authorize('update', $automation_rule);

        $account = auth()->user()->account;
        $users = $account->users()->orderBy('firstname')->get();
        $teams = $account->teams;
        $labels = $account->labels;

        return view('Chat::settings.automation-rules.edit', [
            'Automation' => $automation_rule,
            'users' => $users,
            'teams' => $teams,
            'labels' => $labels,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Automation $automation_rule)
    {
        $this->authorize('update', $automation_rule);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_name' => 'required|string|max:255',
            'conditions' => 'required|array',
            'actions' => 'required|array',
            'active' => 'boolean',
        ]);

        $automation_rule->update($validated);

        return redirect()->route('settings.chat.automation-rules.index')
            ->with('success', 'Automation rule updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Automation $automation_rule)
    {
        $this->authorize('delete', $automation_rule);

        $automation_rule->delete();

        return redirect()->route('settings.chat.automation-rules.index')
            ->with('success', 'Automation rule deleted successfully.');
    }

    /**
     * Toggle active status
     */
    public function toggleActive(Automation $automationRule)
    {
        $this->authorize('update', $automationRule);

        $automationRule->update(['active' => ! $automationRule->active]);

        return redirect()->back()
            ->with('success', 'Automation rule status updated.');
    }

    /**
     * Restore a soft-deleted automation.
     */
    public function restore($id)
    {
        $automation = Automation::withTrashed()->findOrFail($id);
        $this->authorize('restore', $automation);

        $automation->restore();

        return redirect()->route('settings.chat.automation-rules.index')
            ->with('success', 'Automation rule restored successfully.');
    }

    /**
     * Permanently delete an automation.
     */
    public function forceDelete($id)
    {
        $automation = Automation::withTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $automation);

        $name = $automation->name;
        $automation->forceDelete();

        return redirect()->route('settings.chat.automation-rules.index')
            ->with('success', "Automation rule '{$name}' permanently deleted.");
    }
}
