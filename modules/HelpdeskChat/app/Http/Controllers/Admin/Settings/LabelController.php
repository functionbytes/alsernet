<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Settings;

use Illuminate\Http\Request;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Label;

class LabelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $labels = auth()->user()->account->labels()
            ->orderBy('show_on_sidebar', 'desc')
            ->orderBy('title')
            ->paginate(20);

        return view('helpdeskchat::admin.settings.labels.index', compact('labels'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('helpdeskchat::admin.settings.labels.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'required|string|max:7',
            'show_on_sidebar' => 'boolean',
        ]);

        auth()->user()->account->labels()->create($validated);

        return redirect()->route('admin.helpdesk.labels.index')
            ->with('success', 'Label created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Label $label)
    {
        $this->authorize('view', $label);

        return view('helpdeskchat::admin.settings.labels.show', compact('label'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Label $label)
    {
        $this->authorize('update', $label);

        return view('helpdeskchat::admin.settings.labels.edit', compact('label'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Label $label)
    {
        $this->authorize('update', $label);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'required|string|max:7',
            'show_on_sidebar' => 'boolean',
        ]);

        $label->update($validated);

        return redirect()->route('admin.helpdesk.labels.index')
            ->with('success', 'Label updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Label $label)
    {
        $this->authorize('delete', $label);

        $label->delete();

        return redirect()->route('admin.helpdesk.labels.index')
            ->with('success', 'Label deleted successfully.');
    }
}
