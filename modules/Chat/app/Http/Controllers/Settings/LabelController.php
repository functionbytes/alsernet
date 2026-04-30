<?php

namespace Modules\Chat\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Conversations\ConversationLabel;

class LabelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ConversationLabel::where('account_id', auth()->user()->account_id);

        if ($request->input('tab') === 'trashed') {
            $query->onlyTrashed();
        }

        $query->orderBy('show_on_sidebar', 'desc')
            ->orderBy('name');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $labels = $query->paginate(20);

        // Calculate stats
        $allLabels = ConversationLabel::where('account_id', auth()->user()->account_id)->get();
        $trashedCount = ConversationLabel::where('account_id', auth()->user()->account_id)->onlyTrashed()->count();

        $stats = [
            'total' => $allLabels->count(),
            'on_sidebar' => $allLabels->where('show_on_sidebar', true)->count(),
            'hidden' => $allLabels->where('show_on_sidebar', false)->count(),
            'trashed' => $trashedCount,
            'total_usage' => 0, // You can implement this later if you track label usage
        ];

        return view('Chat::settings.labels.index', compact('labels', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('Chat::settings.labels.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'required|string|max:7',
            'show_on_sidebar' => 'boolean',
        ]);

        ConversationLabel::create([...$validated, 'account_id' => auth()->user()->account_id]);

        return redirect()->route('settings.chat.labels.index')
            ->with('success', 'Etiqueta creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ConversationLabel $label)
    {
        $this->authorize('view', $label);

        return view('Chat::settings.labels.show', compact('label'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ConversationLabel $label)
    {
        $this->authorize('update', $label);

        return view('Chat::settings.labels.edit', compact('label'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ConversationLabel $label)
    {
        $this->authorize('update', $label);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'required|string|max:7',
            'show_on_sidebar' => 'boolean',
        ]);

        $label->update($validated);

        return redirect()->route('settings.chat.labels.index')
            ->with('success', 'Etiqueta actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ConversationLabel $label)
    {
        $this->authorize('delete', $label);

        $label->delete();

        return redirect()->route('settings.chat.labels.index')
            ->with('success', 'Etiqueta eliminada exitosamente.');
    }

    /**
     * Restore a soft-deleted label.
     */
    public function restore($id)
    {
        $label = ConversationLabel::withTrashed()->findOrFail($id);
        $this->authorize('restore', $label);

        $label->restore();

        return redirect()->route('settings.chat.labels.index')
            ->with('success', 'Etiqueta restaurada exitosamente.');
    }

    /**
     * Permanently delete a label.
     */
    public function forceDelete($id)
    {
        $label = ConversationLabel::withTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $label);

        $name = $label->name;
        $label->forceDelete();

        return redirect()->route('settings.chat.labels.index')
            ->with('success', "Etiqueta '{$name}' eliminada permanentemente.");
    }
}
