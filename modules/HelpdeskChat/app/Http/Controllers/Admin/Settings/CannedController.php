<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Settings;

use Illuminate\Http\Request;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Canneds\Canned;

class CannedController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Canned::where('account_id', auth()->user()->account_id)
            ->accessibleBy(auth()->user());

        // Search filter
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Visibility filter
        if ($request->filled('visibility')) {
            $query->where('visibility', $request->visibility);
        }

        // Sort by usage or created_at
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        if ($sortBy === 'usage') {
            $query->orderBy('usage_count', $sortDirection);
        } elseif ($sortBy === 'short_code') {
            $query->orderBy('short_code', $sortDirection);
        } else {
            $query->orderBy('created_at', $sortDirection);
        }

        $canneds = $query->with('user')->paginate(20);

        return view('helpdeskchat::admin.settings.canneds.index', compact('canneds'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $availableVariables = Canned::getAvailableVariables();

        return view('helpdeskchat::admin.settings.canneds.create', compact('availableVariables'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'short_code' => 'required|string|max:255',
            'content' => 'required|string',
            'visibility' => 'required|in:personal,team,everyone',
        ]);

        $validated['account_id'] = auth()->user()->account_id;
        $validated['user_id'] = auth()->id();

        Canned::create($validated);

        return redirect()->route('admin.helpdesk.canneds.index')
            ->with('success', 'Canned response created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Canned $Canned)
    {
        $this->authorize('view', $Canned);

        return view('helpdeskchat::admin.settings.canneds.show', compact('Canned'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Canned $Canned)
    {
        $this->authorize('update', $Canned);

        $availableVariables = Canned::getAvailableVariables();

        return view('helpdeskchat::admin.settings.canneds.edit', compact('Canned', 'availableVariables'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Canned $Canned)
    {
        $this->authorize('update', $Canned);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'short_code' => 'required|string|max:255',
            'content' => 'required|string',
            'visibility' => 'required|in:personal,team,everyone',
        ]);

        $Canned->update($validated);

        return redirect()->route('admin.helpdesk.canneds.index')
            ->with('success', 'Canned response updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Canned $Canned)
    {
        $this->authorize('delete', $Canned);

        $Canned->delete();

        return redirect()->route('admin.helpdesk.canneds.index')
            ->with('success', 'Canned response deleted successfully.');
    }

    /**
     * Search canned responses (for API/AJAX).
     */
    public function search(Request $request)
    {
        $query = Canned::where('account_id', auth()->user()->account_id)
            ->accessibleBy(auth()->user());

        if ($request->filled('q')) {
            $query->search($request->q);
        }

        $responses = $query->limit(10)->get(['id', 'short_code', 'title', 'content', 'visibility']);

        return response()->json($responses);
    }

    /**
     * Record usage of a canned response (for API/AJAX).
     */
    public function recordUsage(Canned $Canned)
    {
        $this->authorize('view', $Canned);

        $Canned->recordUsage();

        return response()->json(['success' => true]);
    }
}
