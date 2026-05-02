<?php

namespace Modules\Social\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Social\Models\Label;

class LabelController extends Controller
{
    public function index()
    {
        $labels = Label::where('account_id', auth()->user()->account_id)
            ->withCount('posts')
            ->orderBy('name')
            ->get();

        return view('social::labels.index', compact('labels'));
    }

    public function create()
    {
        return view('social::labels.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
            'description' => 'nullable|string',
        ]);

        Label::create([
            'account_id' => auth()->user()->account_id,
            'name' => $validated['name'],
            'color' => $validated['color'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('admin.social.labels.index')
            ->with('success', 'Etiqueta creada exitosamente');
    }

    public function edit(Label $label)
    {
        $this->authorize('update', $label);

        return view('social::labels.edit', compact('label'));
    }

    public function update(Request $request, Label $label)
    {
        $this->authorize('update', $label);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
            'description' => 'nullable|string',
        ]);

        $label->update($validated);

        return redirect()
            ->route('admin.social.labels.index')
            ->with('success', 'Etiqueta actualizada exitosamente');
    }

    public function destroy(Label $label)
    {
        $this->authorize('delete', $label);

        $label->posts()->detach();
        $label->delete();

        return redirect()
            ->route('admin.social.labels.index')
            ->with('success', 'Etiqueta eliminada exitosamente');
    }
}
