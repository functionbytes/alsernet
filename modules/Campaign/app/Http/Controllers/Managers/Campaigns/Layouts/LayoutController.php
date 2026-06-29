<?php

namespace Modules\Campaign\Http\Controllers\Managers\Campaigns\Layouts;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Campaign\Models\Layout\Layout;

/**
 * Slim LayoutController.
 *
 * Layouts son fragmentos HTML reutilizables (header/footer corporativo)
 * que se incluyen en plantillas. CRUD básico.
 */
class LayoutController extends Controller
{
    public function index(Request $request): View
    {
        $layouts = Layout::query()
            ->when($request->query('q'), fn ($q, $kw) => $q->where('name', 'like', "%{$kw}%"))
            ->orderBy('order')
            ->paginate(20)
            ->withQueryString();

        return view('campaign::manager.layouts.index', compact('layouts'));
    }

    public function create(): View
    {
        return view('campaign::manager.layouts.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'default' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer'],
        ]);

        $layout = Layout::create($data);

        return redirect()
            ->route('manager.campaigns.layouts.edit', $layout->uid)
            ->with('success', 'Layout creado.');
    }

    public function edit(string $uid): View
    {
        $layout = Layout::where('uid', $uid)->firstOrFail();

        return view('campaign::manager.layouts.edit', compact('layout'));
    }

    public function update(Request $request, string $uid): RedirectResponse
    {
        $layout = Layout::where('uid', $uid)->firstOrFail();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'default' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer'],
        ]);

        $layout->update($data);

        return back()->with('success', 'Layout guardado.');
    }

    public function destroy(string $uid): RedirectResponse
    {
        Layout::where('uid', $uid)->firstOrFail()->delete();

        return redirect()
            ->route('manager.campaigns.layouts.index')
            ->with('success', 'Layout eliminado.');
    }

    /**
     * Reordena los layouts vía drag & drop. Recibe { 'order': [uid1, uid2, ...] }.
     */
    public function sort(Request $request): RedirectResponse
    {
        $order = $request->input('order', []);
        foreach ($order as $i => $uid) {
            Layout::where('uid', $uid)->update(['order' => $i]);
        }

        return back()->with('success', 'Orden actualizado.');
    }
}
